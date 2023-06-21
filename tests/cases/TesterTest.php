<?php

require_once 'swearjar/Tester.php';

use PHPUnit\Framework\TestCase;
use Swearjar\Tester;

class TesterTest extends TestCase {

	public Tester $tester;

	public function setUp(): void {
		$this->tester = new Tester;
	}

	public function testProfane(): void {
		$this->assertTrue($this->tester->profane('fuck you john doe'));
		$this->assertTrue($this->tester->profane('FUCK you john doe'));
		$this->assertFalse($this->tester->profane('i love you john doe'));
	}

	public function testScorecard(): void {
		$scorecard = $this->tester->scorecard('fuck you john doe');
		$expected = array('sexual' => 1);
		$this->assertEquals($expected, $scorecard);

		$scorecard = $this->tester->scorecard('fuck you john doe bitch');
		$expected = array('sexual' => 1, 'insult' => 1);
		$this->assertEquals($expected, $scorecard);
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
}
