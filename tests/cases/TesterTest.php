<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Swearjar\Tester;

class TesterTest extends TestCase {

	public Tester $tester;

	public function setUp(): void {
		$this->tester = new Tester();
	}

	public function testProfane(): void {
		$this->assertTrue($this->tester->profane('fuck you john doe'));
		$this->assertTrue($this->tester->profane('FUCK you john doe'));
		$this->assertFalse($this->tester->profane('i love you john doe'));

		$this->assertTrue($this->tester->profane('butt plug'));
		$this->assertTrue($this->tester->profane('I am wearing a butt plug'));
		$this->assertTrue($this->tester->profane('I am wearing a buttplug'));
		$this->assertTrue($this->tester->profane('I love wearing buttplugs'));
	}

	public function testScorecard(): void {
		$scorecard = $this->tester->scorecard('fuck you john doe');
		$expected = array('sexual' => 1);
		$this->assertEquals($expected, $scorecard);

		$scorecard = $this->tester->scorecard('fuck you john doe bitch');
		$expected = array('sexual' => 1, 'insult' => 1);
		$this->assertEquals($expected, $scorecard);
	}

	public function testTypeFiltering(): void {
		$this->assertTrue($this->tester->containsType('pussylicking shitcunt', [Tester::FILTER_SEXUAL]));
		$this->assertTrue($this->tester->containsType('pussylicking shitcunt', [Tester::FILTER_INSULT]));
		$this->assertTrue($this->tester->containsType('pussylicking shitcunt', [Tester::FILTER_SEXUAL, Tester::FILTER_INSULT]));
		$this->assertFalse($this->tester->containsType('pussylicking shitcunt', [Tester::FILTER_BLASPHEMY]));
	}

	public function testCensor(): void {
		$text = $this->tester->censor('John Doe has a massive hard on he is gonna use to fuck everybody');
		$expected = 'John Doe has a massive **** ** he is gonna use to **** everybody';
		$this->assertEquals($expected, $text);

		$text = $this->tester->censor('John Doe has a massive hard on he is gonna use to fuck everybody in the ass', true);
		$expected = 'John Doe has a massive h*** ** he is gonna use to f*** everybody in the a**';
		$this->assertEquals($expected, $text);
	}

	public function testEdgeCases(): void {
		$result = $this->tester->censor("Assasin's Creed Ass");
		$expected = "Assasin's Creed ***";
		$this->assertEquals($expected, $result);
	}

	public function testScanStrictWithUsernames(): void {
		$this->assertTrue($this->tester->scanStrict('ImAPussyFucker3000'));
		$this->assertTrue($this->tester->scanStrict('Buttplug2Tester'));
		$this->assertFalse($this->tester->scanStrict('Longusernamewithnocursewords'));
		$this->assertTrue($this->tester->scanStrict('AssasinsCreed')); // Expected to be a false positive, but it's strict
	}

	public function testScanStrictWithUsernamesUsingLeetCode(): void {
		$this->tester->enableLeetcodeDetection();
		$this->assertTrue($this->tester->scanStrict('Th3P0rn0M45ter'));
		$this->assertTrue($this->tester->scanStrict('\/@91n@')); // A bit extreme, but thoroughly tests the leet code
	}
}
