<?php
App::uses('View', 'View');
App::uses('Helper', 'View');
App::uses('GoogleCalendarHelper', 'Icing.View/Helper');
/**
 * GoogleCalendarHelper Test Case
 *
 */
class GoogleCalendarHelperTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$View = new View();
		$this->GoogleCalendar = new GoogleCalendarHelper($View);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->GoogleCalendar);

		parent::tearDown();
	}
	
	public function testQuick(){
		$result = $this->GoogleCalendar->quick("Dinner with Michael at 7pm", array('url_only' => true));
		$this->assertEqual('http://www.google.com/calendar/event?action=TEMPLATE&pprop=HowCreated%3AQUICKADD&ctext=Dinner+with+Michael+at+7pm', $result);
	}
	
	public function testQuickForm(){
		$result = $this->GoogleCalendar->quickForm();
		$this->assertEqual('<form action="http://www.google.com/calendar/event?" target="_blank" id="Form" method="get" accept-charset="utf-8"><input type="hidden" name="action" value="TEMPLATE" id="action"/><input type="hidden" name="pprop" value="HowCreated:QUICKADD" id="pprop"/><input name="ctext" type="text" id="ctext"/><input  type="submit" value="Add"/></form>', $result);
	}

/**
 * testReminder method
 *
 * @return void
 */
	public function testReminder() {
		$result = $this->GoogleCalendar->reminder('small', array(
			'start' => 'Aug 15th, 2013 8:00pm',
			'end' => 'Aug 15th, 2013 9:00pm',
			'title' => 'Test Event',
			'details' => 'Details of Event',
			'location' => 'Albuquerque, NM',
			'add' => array('nurvzy@gmail.com', 'nick.baker@audiologyholdings.com'),
			'url_only' => true
		));
		$this->assertEqual('http://www.google.com/calendar/event?action=TEMPLATE&dates=20130816T020000Z%2F20130816T030000Z&text=Test+Event&details=Details+of+Event&location=Albuquerque%2C+NM&trp=true&add=nurvzy@gmail.com&add=nick.baker@audiologyholdings.com', $result);
		
		$result = $this->GoogleCalendar->reminder('small', array(
			'start' => 'Aug 15th, 2013 8:00pm',
			'end' => 'Aug 15th, 2013 9:00pm',
			'title' => 'Test Event',
			'details' => 'Details of Event',
			'location' => 'Albuquerque, NM',
			'add' => array('nurvzy@gmail.com', 'nick.baker@audiologyholdings.com'),
		));
		$this->assertEqual('<a href="http://www.google.com/calendar/event?action=TEMPLATE&amp;dates=20130816T020000Z%2F20130816T030000Z&amp;text=Test+Event&amp;details=Details+of+Event&amp;location=Albuquerque%2C+NM&amp;trp=true&amp;add=nurvzy@gmail.com&amp;add=nick.baker@audiologyholdings.com"><img src="http://www.google.com/calendar/images/ext/gc_button1.gif" alt="" /></a>', $result);
	}

}
