<?php
/**
 * This is an example model, which could be used for database based
 * configurations, if you had need of them.
 *
 * It works in conjunction with the Conf class, to allow you to have
 * admin-editable configurations, available within a simple and consistant
 * Conf (static access) read interface.
 *
 * Note: Conf::write() would never update a database record, you'd need to save
 * onto the model, like normal, via standard CRUD controller/actions
 */
Class DatabaseConfiguration extends IcingAppModel {
	public $name = 'DatabaseConfiguration';
	public $primaryKey = 'key';
	public $useTable = 'database_configurations';

}
