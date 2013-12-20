<?php

/**
 * BMUtility: utility classes
 *
 * @author Julian
 */

// Class to hold and search all the possible combinations that a skill
// attack could hit
//
// It's huge and complicated, but hopefully better than a naive search.
class BMUtilityHitTable {
    private $dice = array();

    // $hits is an array keyed by numbers. Values is an array, keyed
    // by the combined unique ids of the sets of dice used to make the value
    //
    // So, if 4 can be made with A and B or C and D,
    // $hits[4] = [ AB => [ dieA, dieB ], CD => [ dieC, dieD ] ]
    private $hits = array();

    public function __construct($dice) {
        // For building hash keys, every die needs a unique
        // identifier, no matter how many there are, but if there are
        // more than 36 dice, something is very, very wrong.
        $ids = str_split("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789");
        for ($i = 0; $i < count($dice); $i++) {
            $die = $dice[$i];
            $die_id = $ids[$i];

            $this->dice[] = $die;

            foreach (array_keys($this->hits) as $target) {
                foreach ($this->hits[$target] as $key => $combo) {
                    // We've already been used in this combo
                    if (FALSE !== strpos($key, $die_id)) {
                        continue;
                    }

                    foreach ($die->attack_values("Skill") as $val) {
                        $newcombo = $this->hits[$target][$key];
                        $newcombo[] = $die;
                        // the new key will always be sorted, since we
                        // process the dice in order
                        $newkey = $key.$die_id;
                        $newtarget = $target + $val;
                        if (array_key_exists($newtarget, $this->hits)) {
                            // If the same die combo makes a number
                            // two ways, we just overwrite the old
                            // entry.
                            $this->hits[$newtarget][$newkey] = $newcombo;

                        } else {
                            $this->hits[$newtarget] = array($newkey => $newcombo);

                        }
                    }
                }
            }

            // Add the unique values the die may provide
            foreach ($die->attack_values("Skill") as $val) {
                if (array_key_exists($val, $this->hits)) {
                    $this->hits[$val][$die_id] = array($die);
                } else {
                    $this->hits[$val] = array($die_id => array($die));
                }
            }

        }


        // find out if there are any konstant dice present
        $konstantLetterArray = array();

        foreach ($dice as $dieIdx => $die) {
            if ($die->has_skill('Konstant')) {
                $konstantLetterArray[] = $ids[$dieIdx];
            }
        }

        // remove hits that are the result of single-die skill attacks by
        // a konstant die
        if (count($konstantLetterArray) > 0) {
            // for each attacking konstant die
            foreach ($konstantLetterArray as $konstantLetter) {
                // for each possible hit value
                foreach ($this->hits as $val => &$comboArray) {
                    // check whether the hit combinations include the required
                    // single-die skill attack
                    if (array_key_exists($konstantLetter, $comboArray)) {
                        if (1 == count($comboArray)) {
                            // the hit value can be obtained only via the
                            // single-die skill attack, so unset the hit itself
                            unset($this->hits[$val]);
                        } else {
                            // unset the single-die skill attack option, but
                            // leave the hit, since some other combination can
                            // still achieve it
                            unset($comboArray[$konstantLetter]);
                        }
                        // move on to the next konstant die
                        continue 2;
                    }
                }
            }
        }
    }

    // Test for a hit. Return all possible sets of dice that can make that hit.
    public function find_hit($target) {
        if (array_key_exists($target, $this->hits)) {
            return array_values($this->hits[$target]);
        }
        return FALSE;
    }

    // Return a list of all possible hits
    public function list_hits() {
        return array_keys($this->hits);
    }
}

?>