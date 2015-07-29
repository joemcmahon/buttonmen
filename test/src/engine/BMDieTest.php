<?php

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2012-12-01 at 14:50:59.
 */
class BMDieTest extends PHPUnit_Framework_TestCase {

    /**
     * @var BMDie
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->object = new BMDie;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {

    }

    protected static function getMethod($name) {
        $class = new ReflectionClass('BMDie');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * @covers BMDie::init
     */
    public function testInit() {
        $this->object->init(6, array("TestDummyBMSkillTesting" => "Testing"));

        $this->assertEquals($this->object->min, 1);
        $this->assertEquals($this->object->max, 6);

        $this->assertTrue($this->object->has_skill("Testing"));

        $this->object->init(14, array("TestDummyBMSkillTesting2" => "Testing2"));

        $this->assertEquals($this->object->min, 1);
        $this->assertEquals($this->object->max, 14);

        $this->assertTrue($this->object->has_skill("Testing2"));

        // init does not remove old skills, or otherwise reset variables
        // at the moment. It's for working on brand-new dice
        $this->assertTrue($this->object->has_skill("Testing"));
    }

    /**
     * @covers BMDie::create
     *
     * @depends testInit
     */
    public function testCreate() {
        $die = BMDie::create(6, array());

        $this->assertInstanceOf('BMDie', $die);
        $this->assertEquals(1, $die->min);
        $this->assertEquals(6, $die->max);

        try {
            $die = BMDie::create(-15, array());
            $this->fail('Creating out-of-range die did not throw an exception.');
        } catch (UnexpectedValueException $e) {

        }

        $this->assertEquals(6, $die->max);

        // try some more bad values
        try {
            $die = BMDie::create(1023, array());
            $this->fail('Creating out-of-range die did not throw an exception.');
        } catch (UnexpectedValueException $e) {

        }

        $die = BMDie::create(0, array());
        $this->assertInstanceOf('BMDie', $die);
        $this->assertEquals(0, $die->min);
        $this->assertEquals(0, $die->max);

        try {
            $die = BMDie::create(100, array());
            $this->fail('Creating out-of-range die did not throw an exception.');
        } catch (UnexpectedValueException $e) {

        }

        // downright illegal values
        try {
            $die = BMDie::create("thing", array());
            $this->fail('Creating non-numeric die did not throw an exception.');
        } catch (UnexpectedValueException $e) {

        }

        try {
            $die = BMDie::create("4score", array());
            $this->fail('Creating non-numeric die did not throw an exception.');
        } catch (UnexpectedValueException $e) {

        }

        try {
            $die = BMDie::create(2.718, array());
            $this->fail('Creating non-numeric die did not throw an exception.');
        } catch (UnexpectedValueException $e) {

        }

        try {
            $die = BMDie::create("thing8", array());
            $this->fail('Creating non-numeric die did not throw an exception.');
        } catch (UnexpectedValueException $e) {

        }
    }

    /*
     * @covers BMDie::create_from_recipe
     */

    public function testCreate_from_recipe() {
        $die = BMDie::create_from_recipe('ps(6)');
        $this->assertTrue($die->has_skill('Poison'));
        $this->assertTrue($die->has_skill('Shadow'));
        $this->assertEquals(6, $die->max);
    }

    /*
     * @covers BMDie::parse_recipe_for_sides
     */

    public function testParse_recipe_for_sides() {
        $parse = self::getMethod('parse_recipe_for_sides');
        $this->assertEquals('4', $parse->invokeArgs(NULL, array('(4)')));
        $this->assertEquals('4', $parse->invokeArgs(NULL, array('ps(4)')));
        $this->assertEquals('4', $parse->invokeArgs(NULL, array('(4)+')));
        $this->assertEquals('4', $parse->invokeArgs(NULL, array('ps(4)+')));

        $this->assertEquals('X', $parse->invokeArgs(NULL, array('(X)')));
        $this->assertEquals('X', $parse->invokeArgs(NULL, array('ps(X)')));
        $this->assertEquals('X', $parse->invokeArgs(NULL, array('(X)+')));
        $this->assertEquals('X', $parse->invokeArgs(NULL, array('ps(X)+')));
    }

    /*
     * @covers BMDie::parse_recipe_for_skills
     */

    public function testParse_recipe_for_skills() {
        $parse = self::getMethod('parse_recipe_for_skills');
        $this->assertEquals(array(), $parse->invokeArgs(NULL, array('(4)')));
        $this->assertEquals(array('Poison', 'Shadow'), $parse->invokeArgs(NULL, array('ps(4)')));
        $this->assertEquals(array('Poison'), $parse->invokeArgs(NULL, array('(4)p')));
        $this->assertEquals(array('Poison', 'Shadow'), $parse->invokeArgs(NULL, array('p(4)s')));

        $this->assertEquals(array(), $parse->invokeArgs(NULL, array('(X)')));
        $this->assertEquals(array('Poison', 'Shadow'), $parse->invokeArgs(NULL, array('ps(X)')));
        $this->assertEquals(array('Poison'), $parse->invokeArgs(NULL, array('(X)p')));
        $this->assertEquals(array('Poison', 'Shadow'), $parse->invokeArgs(NULL, array('p(X)s')));
    }

    /**
     * @covers BMDie::create_from_string_components
     *
     * @depends testCreate
     */
    public function testCreate_from_string_components() {
        // We only test creation of standard die types here.
        // (and errors)
        //
        // The complex types can work this function out in their own
        // test suites

        $create = self::getMethod('create_from_string_components');

        $die = $create->invokeArgs(NULL, array('72'));
        $this->assertInstanceOf('BMDie', $die);
        $this->assertEquals(72, $die->max);

        $die = $create->invokeArgs(NULL, array('himom!'));
        $this->assertNull($die);

        $die = $create->invokeArgs(NULL, array('75.3'));
        $this->assertNull($die);

        $die = $create->invokeArgs(NULL, array('trombones76'));
        $this->assertNull($die);

        $die = $create->invokeArgs(NULL, array('76trombones'));
        $this->assertNull($die);
    }

    /**
     * @covers BMDie::activate
     */
    public function testActivate() {
        $game = new TestDummyGame;
        $this->object->ownerObject = $game;
        $this->object->activate('player');
        $newDie = $game->dice[0][1];

        $this->assertInstanceOf('BMDie', $newDie);

        $this->assertTrue($game === $newDie->ownerObject);

        // Make the dice equal in value

        $this->assertFalse(($this->object === $newDie), "activate returned the same object.");
    }

    /**
     * @coversNothing
     */
    public function testIntegrationActivate() {
        $game = new BMGame;
        $game->activeDieArrayArray = array(array(), array());
        $this->object->ownerObject = $game;
        $this->object->playerIdx = 1;
        $this->object->activate();
        $this->assertInstanceOf('BMDie', $game->activeDieArrayArray[1][0]);
    }

    /**
     * @covers BMDie::roll
     *
     * @depends testInit
     */
    public function testRoll() {
        $this->object->init(6, array());
        $rolls = array_fill(1, 6, 0);

        for ($i = 0; $i < 300; $i++) {
            $this->object->roll(FALSE);
            if ($this->object->value < 1 || $this->object->value > 6) {
                $this->assertFalse(TRUE, "Die rolled out of bounds during FALSE.");
            }

            $rolls[$this->object->value]++;
        }

        for ($i = 0; $i < 300; $i++) {
            $this->object->roll(TRUE);
            if ($this->object->value < 1 || $this->object->value > 6) {
                $this->assertFalse(TRUE, "Die rolled out of bounds during TRUE.");
            }

            $rolls[$this->object->value]++;
        }

        // How's our randomness?
        //
        // We're only testing for "terrible" here.
        for ($i = 1; $i <= 6; $i++) {
            $this->assertGreaterThan(25, $rolls[$i], "randomness dubious for $i");
            $this->assertLessThan(175, $rolls[$i], "randomness dubious for $i");
        }

        // test locked-out rerolls
        $val = $this->object->value;

        $this->object->doesReroll = FALSE;

        for ($i = 0; $i < 20; $i++) {
            // Test both on successful attack and not
            $this->object->roll($i % 2);
            $this->assertEquals($val, $this->object->value, "Die value changed.");
        }
    }

    /**
     * @covers BMDie::make_play_die
     *
     * @depends testRoll
     * @depends testInit
     */
    public function testMake_play_die() {
        $this->object->init(6, array());

        $newDie = $this->object->make_play_die();

        $this->assertInstanceOf('BMDie', $newDie);

        $this->assertGreaterThanOrEqual(1, $newDie->value);
        $this->assertLessThanOrEqual(6, $newDie->value);

        // Make the dice equal in value

        $this->object->value = $newDie->value;

        $this->assertFalse(($this->object === $newDie), "make_play_die returned the same object.");
    }

    /*
     * @covers BMDie::attack_list
     */

    public function testAttack_list() {
        $this->assertNotEmpty($this->object->attack_list());
        $this->assertContains("Skill", $this->object->attack_list());
        $this->assertContains("Power", $this->object->attack_list());
        $this->assertNotEmpty($this->object->attack_list());
        $this->assertEquals(2, count($this->object->attack_list()));
    }

    /**
     * @covers BMDie::attack_values
     *
     * @depends testInit
     * @depends testAttack_list
     */
    public function testAttack_values() {
        $this->object->init(15);
        $this->object->value = 7;

        foreach ($this->object->attack_list() as $att) {
            $this->assertNotEmpty($this->object->attack_values($att));
            $this->assertContains(7, $this->object->attack_values($att));
            $this->assertEquals(1, count($this->object->attack_values($att)));
        }

        $this->assertNotEmpty($this->object->attack_values("Bob"));
        $this->assertContains(7, $this->object->attack_values("Bob"));
        $this->assertEquals(1, count($this->object->attack_values("Bob")));

        $this->object->value = 4;
        foreach ($this->object->attack_list() as $att) {
            $this->assertNotEmpty($this->object->attack_values($att));
            $this->assertContains(4, $this->object->attack_values($att));
            $this->assertEquals(1, count($this->object->attack_values($att)));
        }
    }

    /**
     * @covers BMDie::defense_value
     *
     * @depends testInit
     * @depends testRoll
     * @depends testAttack_list
     */
    public function testDefense_value() {
        $this->object->init(6, array());

        foreach ($this->object->attack_list() as $att) {
            for ($i = 0; $i < 10; $i++) {
                $this->object->roll(FALSE);
                $this->assertEquals($this->object->value, $this->object->defense_value($att), "Defense value fails to equal value for $att.");
            }
        }
    }

    /**
     * @covers BMDie::get_scoreValueTimesTen
     *
     * @depends testInit
     */
    public function testGet_scoreValueTimesTen() {
        $this->object->init(7, array());

        $this->assertEquals(35, $this->object->get_scoreValueTimesTen());

        $this->object->captured = TRUE;

        $this->assertEquals(70, $this->object->get_scoreValueTimesTen());
    }

    /**
     * @covers BMDie::initiative_value
     *
     * @depends testInit
     * @depends testRoll
     */
    public function testInitiative_value() {
        $this->object->init(6, array());
        $this->object->roll(FALSE);

        $val = $this->object->initiative_value();
        $this->assertEquals($val, $this->object->value);
    }

    /**
     * @covers BMDie::assist_values
     *
     * @depends testAttack_list
     */
    public function testAssist_values() {
        $attDie = new BMDie;
        $defDie = new BMDie;

        foreach ($this->object->attack_list() as $att) {
            $assistVals = $this->object->assist_values($att, array($attDie), array($defDie));
            $this->assertNotEmpty($assistVals);
            $this->assertEquals(1, count($assistVals));
            $this->assertEquals(0, $assistVals[0]);
        }

        // test that we don't assist attacks we are making
        $this->object->add_skill("AVTesting", "TestDummyBMSkillAVTesting");

        // test that the assist skill works
        $assistVals = $this->object->assist_values($att, array($attDie), array($defDie));
        $this->assertNotEmpty($assistVals);
        $this->assertEquals(2, count($assistVals));
        $this->assertEquals(-1, $assistVals[0]);
        $this->assertEquals(1, $assistVals[1]);

        // now make it not work
        $assistVals = $this->object->assist_values($att, array($this->object), array($defDie));
        $this->assertNotEmpty($assistVals);
        $this->assertEquals(1, count($assistVals));
        $this->assertEquals(0, $assistVals[0]);

        $assistVals = $this->object->assist_values($att, array($attDie, $this->object), array($defDie));
        $this->assertNotEmpty($assistVals);
        $this->assertEquals(1, count($assistVals));
        $this->assertEquals(0, $assistVals[0]);

        // test that two identical Fire dice do not conflict when one is in the attack and
        // the other one isn't
        $att2 = clone $this->object;
        $assistVals = $this->object->assist_values($att, array($att2), array($defDie));
        $this->assertNotEmpty($assistVals);
        $this->assertEquals(2, count($assistVals));
        $this->assertEquals(-1, $assistVals[0]);
        $this->assertEquals(1, $assistVals[1]);
    }

    /**
     * @covers BMDie::attack_contribute
     *
     * @depends testAttack_list
     * @depends testAssist_values
     */
    public function testAttack_contribute() {
        $attDie = new BMDie;
        $defDie = new BMDie;

        foreach ($this->object->attack_list() as $att) {
            $this->assertFalse($this->object->attack_contribute($att, array($attDie), array($defDie), 1));
            $this->assertFalse($this->object->attack_contribute($att, array($attDie), array($defDie), 0));
        }
    }

    /**
     * @covers BMDie::is_valid_attacker
     *
     * @depends testAttack_list
     */
    public function testIs_valid_attacker() {
        $attDie = new BMDie;

        foreach ($this->object->attack_list() as $att) {

            $this->assertFalse($this->object->is_valid_attacker(array($attDie)));
            $this->assertTrue($this->object->is_valid_attacker(array($this->object)));
            $this->assertTrue($this->object->is_valid_attacker(array($this->object, $attDie)));
        }
    }

    /**
     * @covers BMDie::is_valid_target
     *
     * @depends testAttack_list
     */
    public function testIs_valid_target() {
        $defDie = new BMDie;

        foreach ($this->object->attack_list() as $att) {

            $this->assertFalse($this->object->is_valid_target(array($defDie)));
            $this->assertTrue($this->object->is_valid_target(array($this->object)));
            $this->assertTrue($this->object->is_valid_target(array($this->object, $defDie)));
        }

        // james: test needs to be reactivated when Warrior skill is added
//        $this->object->add_skill('Warrior') = TRUE;
//        $this->assertFalse($this->object->is_valid_target(array($this->object)));
    }

    /**
     * @covers BMDie::capture
     *
     * @depends testAttack_list
     */
    public function testCapture_normal() {
        $attDie = BMDie::create(7);
        $defDie = BMDie::create(11);
        $attackers = array($attDie);
        $defenders = array($defDie);

        foreach ($this->object->attack_list() as $att) {
            $this->object->capture($att, $attackers, $defenders);
            $this->assertEquals(7, $attDie->max);
        }
    }

    /**
     * @covers BMDie::capture
     *
     * @depends testAttack_list
     */
    public function testCapture_morphing() {
        $attDie = BMDie::create(8);
        $attDie->add_skill('Morphing');
        $defDie = BMDie::create_from_recipe('(6,6)');
        $defDie->captured = TRUE;

        $game = new BMGame;
        $game->activeDieArrayArray = array(array($attDie), array($defDie));
        $game->attack = array(0, 1, array(0), array(0), 'Power');

        $attDie->ownerObject = $game;
        $attDie->playerIdx = 0;
        $defDie->ownerObject = $game;

        $attackers = array($attDie);
        $defenders = array($defDie);

        $attDie->capture('Power', $attackers, $defenders);

        $newDie = $attackers[0];

        $this->assertInstanceOf('BMDieTwin', $newDie);
        $this->assertEquals(2, $newDie->min);
        $this->assertEquals(12, $newDie->max);
    }

    /**
     * @covers BMDie::be_captured
     *
     * @depends testAttack_list
     */
    public function testBe_captured() {
        $attDie = new BMDie;

        $this->assertFalse($this->object->captured);
        $attackers = array($attDie);
        $defenders = array($this->object);

        foreach ($this->object->attack_list() as $att) {
            $this->object->be_captured($att, $attackers, $defenders);
        }
    }

    /*
     * @covers BMDie::describe
     */

    public function testDescribe() {
        $this->object->init(6);
        $this->assertEquals('6-sided die', $this->object->describe(TRUE));
        $this->assertEquals('6-sided die', $this->object->describe(FALSE));

        $this->object->roll();
        $value = $this->object->value;
        $this->assertEquals("6-sided die showing {$value}", $this->object->describe(TRUE));
        $this->assertEquals('6-sided die', $this->object->describe(FALSE));

        $this->object->add_skill('Poison');
        $this->object->add_skill('Shadow');
        $this->assertEquals(
            "Poison Shadow 6-sided die showing {$value}", $this->object->describe(TRUE)
        );
        $this->assertEquals('Poison Shadow 6-sided die', $this->object->describe(FALSE));
    }

    /**
     * @covers BMDie::split
     *
     * @depends testInit
     * @depends testRoll
     */
    public function testSplit() {
        // 1-siders split into a 1-sider and a 0-sider
        $this->object->init(1, array());
        $this->object->roll(FALSE);

        $dice = $this->object->split();

        $this->assertFalse($dice[0] === $dice[1]);
        $this->assertTrue($this->object === $dice[0]);
        $this->assertNotEquals($dice[0]->max, $dice[1]->max);
        $this->assertEquals(1, $dice[0]->max);
        $this->assertEquals(0, $dice[1]->max);

        // even-sided split
        $this->object->init(12, array());
        $this->object->roll(FALSE);

        $dice = $this->object->split();

        $this->assertFalse($dice[0] === $dice[1]);
        $this->assertTrue($this->object === $dice[0]);
        $this->assertEquals($dice[0]->max, $dice[1]->max);
        $this->assertEquals(6, $dice[0]->max);

        // odd-sided split
        $this->object->init(7, array());
        $this->object->roll(FALSE);

        $dice = $this->object->split();

        $this->assertFalse($dice[0] === $dice[1]);
        $this->assertTrue($this->object === $dice[0]);
        $this->assertNotEquals($dice[0]->max, $dice[1]->max);

        // The order of arguments for assertGreaterThan is screwy.
        $this->assertGreaterThan($dice[1]->max, $dice[0]->max);
        $this->assertEquals(4, $dice[0]->max);
        $this->assertEquals(3, $dice[1]->max);
    }

    /*
     * @covers BMDie::shrink
     */
    public function testShrink() {
        $die = $this->object;
        $die->init(99);
        $die->shrink();
        $this->assertEquals(30, $die->max);
        $die->shrink();
        $this->assertEquals(20, $die->max);
        $die->shrink();
        $this->assertEquals(16, $die->max);
        $die->shrink();
        $this->assertEquals(12, $die->max);
        $die->shrink();
        $this->assertEquals(10, $die->max);
        $die->shrink();
        $this->assertEquals(8, $die->max);
        $die->shrink();
        $this->assertEquals(6, $die->max);
        $die->shrink();
        $this->assertEquals(4, $die->max);
        $die->shrink();
        $this->assertEquals(2, $die->max);
        $die->shrink();
        $this->assertEquals(1, $die->max);
        $die->shrink();
        $this->assertEquals(1, $die->max);
    }

    /*
     * @covers BMDie::grow
     */
    public function testGrow() {
        $die = $this->object;
        $die->init(1);
        $die->grow();
        $this->assertEquals(2, $die->max);
        $die->grow();
        $this->assertEquals(4, $die->max);
        $die->grow();
        $this->assertEquals(6, $die->max);
        $die->grow();
        $this->assertEquals(8, $die->max);
        $die->grow();
        $this->assertEquals(10, $die->max);
        $die->grow();
        $this->assertEquals(12, $die->max);
        $die->grow();
        $this->assertEquals(16, $die->max);
        $die->grow();
        $this->assertEquals(20, $die->max);
        $die->grow();
        $this->assertEquals(30, $die->max);
        $die->grow();
        $this->assertEquals(30, $die->max);
    }

    /*
     * @covers BMDie::get_recipe
     */

    public function testGet_recipe() {
        $die0 = new BMDie;
        $die0->init(51, array());
        $this->assertEquals('(51)', $die0->get_recipe());

        $die1 = new BMDie;
        $die1->init(6, array('Poison'));
        $this->assertEquals('p(6)', $die1->get_recipe());

        $die2 = new BMDie;
        $die2->init(5, array('Shadow'));
        $this->assertEquals('s(5)', $die2->get_recipe());

        $die3 = new BMDie;
        $die3->init(13, array('Poison', 'Shadow'));
        $this->assertEquals('ps(13)', $die3->get_recipe());

        $die4 = new BMDie;
        $die4->init(25, array('Shadow', 'Poison'));
        $this->assertEquals('sp(25)', $die4->get_recipe());

        $die5 = new BMDie;
        $die5->init(51, array());
        $this->assertEquals('(51)', $die5->get_recipe(TRUE));
    }

    /*
     * @covers BMDie::has_flag
     */
    public function testHas_flag() {
        $this->assertFalse($this->object->has_flag('flag'));
    }

    /*
     * @covers BMDie::add_flag
     */
    public function testAdd_flag() {
        $this->object->add_flag('WasJustCaptured');
        $this->assertTrue($this->object->has_flag('WasJustCaptured'));

        $this->object->add_flag('WasJustCaptured');
        $this->assertTrue($this->object->has_flag('WasJustCaptured'));
    }

    /*
     * @covers BMDie::remove_flag
     */
    public function testRemove_flag() {
        $this->object->add_flag('WasJustCaptured');
        $this->assertTrue($this->object->has_flag('WasJustCaptured'));

        $this->object->remove_flag('WasJustCaptured');
        $this->assertFalse($this->object->has_flag('WasJustCaptured'));
    }

    /*
     * @covers BMDie::remove_all_flags
     */
    public function testRemove_all_flags() {
        $this->object->add_flag('WasJustCaptured');
        $this->object->remove_all_flags();
        $this->assertFalse($this->object->has_flag('WasJustCaptured'));
    }

    /*
     * @covers BMDie::flags_as_string
     */
    public function testFlags_as_string() {
        $this->object->add_flag('WasJustCaptured');
        $this->assertEquals('WasJustCaptured', $this->object->flags_as_string());
    }

    /*
     * @covers BMDie::load_flags_from_string
     */
    public function testLoad_flags_from_string() {
        $this->object->load_flags_from_string('WasJustCaptured');
        $this->assertTrue($this->object->has_flag('WasJustCaptured'));
    }

    public function test__get() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function test__set() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function test__toString() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function test__clone() {
        // Doesn't do anything at the moment.
        $this->assertTrue(TRUE);
    }

}
