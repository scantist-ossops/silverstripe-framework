<?php
/**
 * Represents a column in the database with the type 'Time'.
 * 
 * Example definition via {@link DataObject::$db}:
 * <code>
 * static $db = array(
 * 	"StartTime" => "Time",
 * );
 * </code>
 * 
 * @todo Add localization support, see http://open.silverstripe.com/ticket/2931
 * 
 * @package framework
 * @subpackage model
 */
class Time extends DBField {

	public function setValue($value, $record = null) {
		if($value) {
			if(preg_match( '/(\d{1,2})[:.](\d{2})([a|A|p|P|][m|M])/', $value, $match )) $this->TwelveHour( $match );
			else $this->value = date('H:i:s', strtotime($value));
		} else { 
			$value = null;
		}
	}

	/**
	 * Return a user friendly format for time depending
	 * on the current {@link i18n::get_time_format()} setting.
	 * It is localized by default depending on {@link i18n::get_locale()}.
	 * Example (for en_US): 23:59
	 * 
	 * @return string
	 */
	public function Nice() {
		if($this->value) return $this->Format(i18n::get_time_format());
	}

	/**
	 * Return a user friendly format for time in a 24 hour format.
	 * 
	 * @return string
	 */
	public function Nice24() {
		if($this->value) return $this->Format('HH:mm');
	}
	
	/**
	 * Return the time using a particular formatting string.
	 *
	 * @see http://framework.zend.com/manual/1.12/en/zend.date.constants.html
	 * @param string $format Format ISO string. Example: "HH:mm"
	 * @return string The date in the requested format
	 */
	public function Format($format) {
		if($this->value){
			$date = new Zend_Date($this->value,'HH:mm:ss');
			return $date->toString($format);
		}
	}
	
	public function TwelveHour( $parts ) {
		$hour = $parts[1];
		$min = $parts[2];
		$half = $parts[3];
		
		// the transmation should exclude 12:00pm ~ 12:59pm
		$this->value = (( (strtolower($half) == 'pm') && $hour != '12') ? $hour + 12 : $hour ) .":$min:00";
	}

	public function requireField() {
		$parts=Array('datatype'=>'time', 'arrayValue'=>$this->arrayValue);
		$values=Array('type'=>'time', 'parts'=>$parts);
		DB::requireField($this->tableName, $this->name, $values);
	}
	
	public function scaffoldFormField($title = null, $params = null) {
		$field = TimeField::create($this->name, $title);
		
		// Show formatting hints for better usability
		$field->setDescription(sprintf(
			_t('FormField.Example', 'e.g. %s', 'Example format'),
			Convert::raw2xml(Zend_Date::now()->toString($field->getConfig('timeformat')))
		));
		$field->setAttribute('placeholder', $field->getConfig('timeformat'));
		
		return $field;
	}
	
}
