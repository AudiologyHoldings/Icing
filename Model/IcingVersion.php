<?php
App::uses('IcingAppModel', 'Icing.Model');
/**
 * IcingVersion Model
 *
 * @property User $User
 */
class IcingVersion extends IcingAppModel {

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'model';

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array(
		'model_id' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				'message' => 'Must have a model id',
			),
		),
		'model' => array(
			'notempty' => array(
				'rule' => array('notempty'),
			),
		),
		'json' => array(
			'notempty' => array(
				'rule' => array('notempty'),
			),
		),
		'is_delete' => array(
			'boolean' => array(
				'rule' => array('boolean'),
			),
		),
	);
	
	/**
	* Save The Version data, but first check the limit, and delete the oldest based on created
	* if limit is reached.
	* @param array of data to save
	* @param int versions to keep for a paticular model and id
	*/
	public function saveVersion($data, $versions = false){
		if($versions){
			$conditions = array(
				'model' => $data['model'],
				'model_id' => $data['model_id'],
			);
			$count = $this->find('count', array(
				'conditions' => $conditions 
			));
			if($count >= $versions){
				$last = $this->find('first', array(
					'fields' => array('id'),
					'conditions' => $conditions,
					'order' => array("{$this->alias}.created ASC")
				));
				$this->delete($last[$this->alias]['id']);
			}
		}
		return $this->save($data);
	}
}
