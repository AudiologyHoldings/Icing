<?php
/**
* Show a version details
* 
* Usage:
* echo $this->element('Icing.version', array('version_id' => VERSION_ID));
*/
	$version_id = isset($version_id) ? $version_id : null;
	$version = null;
	if($version_id){
		$version = ClassRegistry::init('Icing.IcingVersion')->findById($version_id);
	}
	Configure::write('debug', 1);
?>
<?php if(!empty($version)): ?>
<div class="icing_version">
	<h1>Version <?php echo $version_id ?></h1>
	<dl class="dl-horizontal">
		<dt><?php echo __('Id'); ?></dt><dd><?php echo h($version['IcingVersion']['id']) ?></dd>
		<dt><?php echo __('Model Id'); ?></dt><dd><?php echo h($version['IcingVersion']['model_id']) ?></dd>
		<dt><?php echo __('Model'); ?></dt><dd><?php echo h($version['IcingVersion']['model']) ?></dd>
		<dt><?php echo __('User Id'); ?></dt><dd><?php echo h($version['IcingVersion']['user_id']) ?></dd>
		<dt><?php echo __('Url'); ?></dt><dd><?php echo h($version['IcingVersion']['url']); ?></dd>
		<dt><?php echo __('IP'); ?></dt><dd><?php echo h($version['IcingVersion']['ip']); ?></dd>
		<dt><?php echo __('is Deleted'); ?></dt><dd><?php echo ($version['IcingVersion']['is_delete']) ? 'Yes' : 'No'; ?></dd>
		<dt><?php echo __('is Minor Version'); ?></dt><dd><?php echo ($version['IcingVersion']['is_minor_version']) ? 'Yes' : 'No'; ?></dd>
		<dt><?php echo __('Created'); ?></dt><dd><?php echo $this->Time->nice($version['IcingVersion']['created']); ?></dd>
	</dl>
	<h2>Data</h2>
	<?php debug(json_decode($version['IcingVersion']['json'], true)) ?>
</div>
<?php endif; ?>
