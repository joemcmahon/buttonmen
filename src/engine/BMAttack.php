<?php

/**
 * BMAttack: attack validation and commital code.
 *
 * @author Julian
 */
class BMAttack {
    protected static $instance = array();

    // True for attacks that do something besides simple capture,
    // because the player may have to choose which attack type to
    // use. Captures are indistinguishable among attacks with no
    // side effects
    public $sideEffect = FALSE;

    public $type;

    // Dice that effect or affect this attack
    protected $validDice = array();

    private function __construct() {
        // You can't instantiate me; I'm a Singleton!
    }

    public static function get_instance($type = NULL) {
        if ($type) {
            $cname = "BMAttack" . ucfirst(strtolower($type));
            if (class_exists($cname)) {
                return $cname::get_instance();
            } else {
                return NULL;
            }
        }

        $class = get_called_class();
        if (!isset(static::$instance[$class])) {
            static::$instance[$class] = new $class;
        }
        static::$instance[$class]->validDice = array();
        return static::$instance[$class];
    }

    public static function possible_attack_types(array $attackers) {
        $allAttackTypesArray = array();

        foreach ($attackers as $attacker) {
            $individualAttackTypeArray = array();
            $individualAttackTypeArray['Power'] = 'Power';
            $individualAttackTypeArray['Skill'] = 'Skill';
            $attacker->run_hooks('attack_list',
                                 array('attackTypeArray' => &$individualAttackTypeArray,
                                       'value' => (int)$attacker->value));

            foreach ($individualAttackTypeArray as $attackType) {
                $allAttackTypesArray[$attackType] = $attackType;
            }
        }

        // james: deliberately ignore Surrender attacks here, so that it
        //        does not appear in the list of attack types

        return $allAttackTypesArray;
    }

    public function add_die(BMDie $die) {
        // need to search with strict on to avoid identical-valued
        // objects matching
        if (!in_array($die, $this->validDice, TRUE)) {
            if (is_array($die->skillList)) {
                foreach ($die->skillList as $skill) {
                    if (FALSE !== array_search($this->type, $skill::incompatible_attack_types())) {
                        return;
                    }
                }
            }
            $this->validDice[] = $die;
        }
    }

    // Figure out what help can be added to the total
    //
    // Returns the minimum and maximum values that can be contributed.
    //
    // $helpers is an array of the sets of returned values from
    // assist_values; we don't need to know which die contributes what
    // here.

    public function help_bounds(array $helpers) {
        $helpMin = $helpMax = 0;

        if (count($helpers) == 0) { return array($helpMin, $helpMax); }

        // Help values are sorted lowest to highest, and we enforce
        // some assumptions about the values to simplify this code a lot
        foreach ($helpers as $helpVals) {
            $min = $helpVals[0];
            $max = end($helpVals);

            if ($max > 0) {
                if ($helpMax > 0) { $helpMax += $max; }
                else { $helpMax = $max; }
            }
            elseif ($max < 0 && $helpMax < 1) {
                // Simplifying assumption here, but life's a lot more
                // complex if there can be gaps in the help coverage.
                $helpMax = -1;
            }

            if ($min < 0) {
                if ($helpMin < 0) { $helpMin += $min; }
                else { $helpMin = $min; }
            }
            elseif ($min > 0 && $helpMin > -1 ) {
                // Simplifying assumption here, but life's a lot more
                // complex if there can be gaps in the help coverage.
                $helpMin = 1;
            }
        }

        return array($helpMin, $helpMax);
    }


    // gather contributions from assisting dice to make the attack work
    // returns FALSE if it failed to do so (user cancel or error)
    //
    // I don't yet understand what the guts of this function looks like
    public function collect_contributions(BMGame $game, array $attackers, array $defenders) {
        $needed = $this->calculate_contributions($game, $attackers, $defenders);

        $amount = $needed[0];
        $helpers = $needed[1];

        if ($amount == 0) { return TRUE; }
        return FALSE;
    }

    // return how much help is needed and who can contribute
    //
    // implemented in subclassed where they actually know what help they need
    public function calculate_contributions($game, array $attackers, array $defenders) {
        return array(0, array());
    }

    // uses the dice in validDice to find a single valid attack within the game
    public function find_attack($game) {
        return FALSE;
    }

    // confirm that an attack is legal
    public function validate_attack($game, array $attackers, array $defenders) {
        return FALSE;
    }

    // actually make the attack
    // Some of this should perhaps be in the game, rather than here.
    public function commit_attack($game, array &$attackers, array &$defenders) {
        // Paranoia
        if (!$this->validate_attack($game, $attackers, $defenders)) {
            return FALSE;
        }

        // Collect the necessary help
        // not implemented yet
//        if (!$this->collect_contributions($game, $attackers, $defenders)) {
//            // return FALSE;
//        }

        if ('Surrender' == $game->attack['attackType']) {
            // this logic is only designed for two players
            $gameScoreArrayArray = $game->gameScoreArrayArray;
            $gameScoreArrayArray[$game->attackerPlayerIdx]['L']++;
            $gameScoreArrayArray[$game->defenderPlayerIdx]['W']++;
            $game->gameScoreArrayArray = $gameScoreArrayArray;
            $game->reset_play_state();
            $game->gameState = BMGameState::endRound;

            return TRUE;
        }

        if ('Pass' == $game->attack['attackType']) {
            $game->nRecentPasses += 1;
        } else {
            $game->nRecentPasses = 0;
        }

        // set attack defaults
        foreach ($attackers as &$att) {
            $att->hasAttacked = TRUE;
            $att->roll(TRUE);
        }

        foreach ($defenders as &$def) {
            $def->captured = TRUE;
        }

        // allow attack type to modify default behaviour
        foreach ($attackers as &$att) {
            $att->capture($this->type, $attackers, $defenders);
        }

        foreach ($defenders as &$def) {
            $def->be_captured($this->type, $attackers, $defenders);
        }

        // process captured dice
        // james: currently only defenders, but could conceivably also include attackers
        foreach ($defenders as &$def) {
            if ($def->captured) {
                $game->capture_die($def);
            }
        }

        return TRUE;
    }


    // methods to find that there is a valid attack
    //
    // If anybody wants to add a many dice vs many dice attack, I will
    // cut then. (It'd _work_, but the words "combinatoric explosion"
    // are deeply relevant.)


    protected function search_onevone($game, $attackers, $defenders) {
        // Sanity check

        if (count($attackers) < 1 || count($defenders) < 1) {
            return FALSE;
        }

        // OK, these aren't necessary for this one, but it's consistent.
        $aIt = new BMUtilityXCYIterator($attackers, 1);
        $dIt = new BMUtilityXCYIterator($defenders, 1);

        foreach ($aIt as $att) {
            foreach ($dIt as $def) {
                if ($this->validate_attack($game, $att, $def)) {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    // Combine the logic for onevmany and manyvone by use of a
    // comparison function.
    protected function search_ovm_helper($game, $one, $many, $compare) {
        // Sanity check

        if (count($many) < 1 || count($one) < 1) {
            return FALSE;
        }

        $count = count($many);

        $oneIt = new BMUtilityXCYIterator($one, 1);

        $checkedSizes = array_fill(1, $count, FALSE);

        for ($i = 1; $i <= $count; $i++) {
            if ($checkedSizes[$i]) {
                continue;
            }

            $manyIt = new BMUtilityXCYIterator($many, $i);

            foreach ($manyIt as $m) {
                foreach ($oneIt as $o) {
                    if ($compare($game, $o, $m)) {
                        return TRUE;
                    }
                }
            }
        }

        return FALSE;
    }

    // $this may not be used in anonymous functions in PHP 5.3. Bastards.
    protected function search_onevmany($game, array $attackers, array $defenders) {
        $myself = $this;
        $compare = function($g, $att, $def) use ($myself) {
            return $myself->validate_attack($g, $att, $def);
        };

        return $this->search_ovm_helper($game, $attackers, $defenders, $compare);
    }

    // It is entirely possible this method will never be used, since
    // skill attacks build a hit table instead. (For hopefully
    // improved efficiency.)
    protected function search_manyvone($game, array $attackers, array $defenders) {
        $myself = $this;
        $compare = function($g, $def, $att) use ($myself) {
            return $myself->validate_attack($g, $att, $def);
        };

        return $this->search_ovm_helper($game, $defenders, $attackers, $compare);
    }

    // returns a list of possible values that can aid an attack
    protected function collect_helpers($game, array $attackers, array $defenders) {
        if (is_null($game->attackerAllDieArray)) {
            return array();
        }

        $helpers = array();
        foreach ($game->attackerAllDieArray as $die) {
            $helpVals = $die->assist_values($this->type, $attackers, $defenders);
            if ($helpVals[0] != 0) {
                $helpers[] = $helpVals;
            }
        }
        return $helpers;
    }

    public function __get($property)
    {
        if (property_exists($this, $property)) {
            switch ($property) {
                default:
                    return $this->$property;
            }
        }
    }

    public function __set($property, $value)
    {
        throw new LogicException(
            "BMAttack->$property cannot be set.");
//        switch ($property) {
//            default:
//                $this->$property = $value;
//        }
    }
}

?>
