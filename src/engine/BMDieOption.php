<?php

class BMDieOption extends BMDie {
    public $optionValueArray;
    public $optionValue;

    protected $needsOptionValue;
    protected $valueRequested;

    public function init($type, array $skills = NULL) {
        $this->min = 1;

        $this->divisor = 1;
        $this->remainder = 0;

        $this->needsOptionValue = TRUE;
        $this->valueRequested = FALSE;

        $this->add_multiple_skills($skills);
    }

    public static function create($recipe, array $skills = NULL) {

        if (!is_string($recipe) ||
            strlen($recipe) < 3 ||
            (FALSE === strpos($recipe, '/'))) {
            throw new UnexpectedValueException("Invalid recipe: $recipe");
        }

        $die = new BMDieOption;

        $die->init($recipe, $skills);

        return $die;
    }

    public function activate() {
        $newDie = clone $this;

        $this->run_hooks(__FUNCTION__, array('die' => $newDie));

        // The clone is the one going into the game, so it's the one
        // that needs a swing value to be set.
        $this->ownerObject->request_swing_values(
            $newDie,
            $newDie->optionValueArray,
            $newDie->playerIdx
        );
        $newDie->valueRequested = TRUE;

        $this->ownerObject->add_die($newDie);
    }

    public function make_play_die() {
        // Get option value from the game before cloning, so it's saved
        // from round to round.
        if ($this->needsOptionValue) {
            $this->ownerObject->require_values();
        }

        return parent::make_play_die();
    }

    public function roll($successfulAttack = FALSE) {
        if ($this->needsOptionValue) {
            if (!$this->valueRequested) {
                $this->ownerObject->request_option_values($this, $this->optionValueArray);
                $this->valueRequested = TRUE;
            }
            $this->ownerObject->require_values();
        }

        parent::roll($successfulAttack);
    }

// Print long description
    public function describe($isValueRequired = FALSE) {
//        if (!is_bool($isValueRequired)) {
//            throw new InvalidArgumentException('isValueRequired must be boolean');
//        }
//
//        $skillStr = '';
//        if (count($this->skillList) > 0) {
//            foreach (array_keys($this->skillList) as $skill) {
//                $skillStr .= "$skill ";
//            }
//        }
//
//        $sideStr = '';
//        if (isset($this->max)) {
//            $sideStr = " (with {$this->max} sides)";
//        }
//
//        $valueStr = '';
//        if ($isValueRequired && isset($this->value)) {
//            $valueStr = " showing {$this->value}";
//        }
//
//        $result = "{$skillStr}{$this->swingType} Swing Die{$sideStr}{$valueStr}";
//
//        return $result;
    }

    public function split() {
        $normalDie = new BMDie();
        // note: init requires an array without string keys
        $normalDie->init($this->max, array_keys($this->skillList));
        $normalDie->ownerObject = $this->ownerObject;
        $normalDie->playerIdx = $this->playerIdx;
        $normalDie->originalPlayerIdx = $this->originalPlayerIdx;
        return $normalDie->split();
    }

//    public function set_optionValue($optionList) {
//        $valid = TRUE;
//
//        if (!array_key_exists($this->swingType, $swingList)) {
//            return FALSE;
//        }
//
//        $sides = (int)$swingList[$this->swingType];
//
//        if ($sides < $this->swingMin || $sides > $this->swingMax) {
//            return FALSE;
//        }
//
//        $this->run_hooks(__FUNCTION__, array('isValid'   => &$valid,
//                                             'swingList' => $swingList));
//
//        if ($valid) {
//            $this->swingValue = $sides;
//            $this->needsSwingValue = FALSE;
//            $this->valueRequested = FALSE;
//            $this->max = $sides;
//            $this->scoreValue = $sides;
//        }
//
//        return $valid;
//    }
}
