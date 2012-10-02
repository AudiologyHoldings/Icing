<?php
/**
  * FileUpload configuration settings
  *
  * save the file to app/Config/file_upload.php
  */
$config = array(
  'FileUpload' => array(
    /**
      * If using the behavior, and a fileNameFunction setting is detected
      * the fileModel setup here will be used by default.
      *
      * fileModel is the name of the model used if we want to 
      *  keep records of uploads in a database.
      */
    'fileModel' => 'Upload',
    /**
      * Component and Behavior Setting.
      *
      * fileVar is the name of the key to look in for an uploaded file
      * For this to work you will need to use the
      * $form-input('Upload.file', array('type'=>'file)); 
      *
      * If you are NOT using a model the input must be just the name of the fileVar
      * input type='file' name='file'
      */
    'fileVar' => 'file',
    
    /**
      * Component and Behavior Setting.
      * 
      * uploadDir is the directory name in the webroot that you want
      * the uploaded files saved to.  default: webroot/files which means
      * webroot/files must exist and set to chmod 777
      */
    'uploadDir' => "files",
    
    /**
      * forceWebroot so that the uploadDir is appened to WWW_ROOT constant setup by
      * CakePHP.  true by default.
      *
      * If forceWebroot is set to false, the uploads will attempt to upload to the exact path
      * of uploadDir.
      */
    'forceWebroot' => true,
    
    /**
      * allowedTypes is the allowed types of files that will be saved
      * to the filesystem.  You can change it at anytime with
      * 
      * takes an associative array or a single value array of extentions
      * 
      * Example:
      * $this->FileUpload->allowedTypes(array(
      *    'jpg' => array('image/jpeg', 'image/pjpeg'), //validate only image/jpeg and image/pjpeg mime types with ext .jpg
      *    'png' => array('image/png'),                 //validate only image/png mime type file with ext .png
      *    'gif',                                       //validate all MIME types for ext .gif
      *    'swf',                                       //validate all MIME types for ext .swf 
      *    'pdf' => array('application/pdf'),           //validate only application/pdf mime type for ext .pdf
      *  ));
      * 
      * @var array of acceptable extensions and their mime types.
      */
    'allowedTypes' => array(
      'jpg' => array('image/jpeg', 'image/pjpeg'),
      'jpeg' => array('image/jpeg', 'image/pjpeg'), 
      'gif' => array('image/gif'),
      'png' => array('image/png','image/x-png'),
    ),
    
    /**
      * Max file size in bytes
      * @var mixed false ignore maxFileSize (php.ini limit). int bytes of max file size
      */
    'maxFileSize' => false,
      
    /**
      * fields are the fields relating to the database columns
      * @var array of fields related to database columns.
      */
    'fields' => array('name'=>'name','type'=>'type','size'=>'size'),
    
    /**
      * required determines and checks if a file was sent to the server.
      * @var boolean
      * - if true: a file is required to be uploaded to save relative records
      * - if false: related records will saved even if there is no uploaded file (default)
      */
    'required' => false,
    /**
      * unique will decide if uploaded files can overwrite other files of the same name
      * if true: uploaded files will never overwrite other files of the same name (default)
      * if false: uploaded files can overwrite files of the same name.
      */
    'unique' => true,
    
    /**
      * Define a model or function callback for the filename.
      * You can define this function within your attaching model
      * or as a stand-alone function somewhere in your app.
      *
      * The function will take in the current fileName to be saved to the
      * database and allow the user to return the desired fileName defined 
      * by the function
      *
      * Note: be sure to return the fileName or the file will not be saved
      *
      * Model callback example:
      *  'fileNameFunction' => 'sanitizeFileName'
      *
      * Example defined in your model:
      *  function sanitizeFileName($fileName){
      *    //Logic for sanitizing your filename
      *    return 'prefix_' . $fileName;
      *  }
      *  
      *  You can also set a standard PHP string parsing like sha1, or md5 for your filenames.
      *
      * Example of using basic PHP string parsing function:
      *  'fileNameFunction' => 'sha1'
      *  'fileNameFunction' => 'md5'
      *  'fileNameFunction' => 'crc32'
      */
    'fileNameFunction' => false
  )
);
