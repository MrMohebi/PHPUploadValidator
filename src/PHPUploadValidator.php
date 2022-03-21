<?php


namespace MrMohebi\PHPUploadValidator;


class PHPUploadValidator
{
    protected array|null $file;
    protected string $filename;
    protected string $tempName;
    protected string $mime;
    protected string $extension;
    protected string $pathToSave;
    protected int $size;
    protected int $allowedMaxSize = 1;
    protected array $allowedMime = [];
    protected array $errors = [];



    public function __construct(string $fileFiledName ,string $pathToSave, string $maxSize = null, array $allowedMime = null, array $allowedContentType = null){
        $this->pathToSave = $pathToSave;
        $this->file = array_key_exists($fileFiledName, $_FILES) ? $_FILES[$fileFiledName] : null;
        if($this->isExist()){
            $this->tempName = $this->file['tmp_name'];
            if(is_uploaded_file($this->tempName)){
                $this->extension = strtolower(substr(strrchr($this->file['name'], '.'), 1));
                $this->size = $this->file['size'];
                $this->filename =  pathinfo($this->file['name'], PATHINFO_FILENAME);
                $this->mime = mime_content_type($this->tempName);

                $this->setValidations($maxSize, $allowedMime, $allowedContentType);
            }else{
                $this->file = null;
            }

        }
    }

    public function upload():bool{
        if($this->isValid()){
            if(!self::createPath($this->pathToSave)) $this->errors["pathToSave"] = "couldn't create path";

            if(move_uploaded_file($this->tempName,$this->pathToSave."/".$this->getNameWithExtension())) return true;
        }
        return false;

    }


    public function setValidations(string $maxSize = null, array $allowedMime = null, array $allowedContentType = null):void{
        if($allowedContentType){
            if(!$allowedMime) $allowedMime = [];
            foreach ($allowedContentType as $eContentType){
                switch($eContentType){
                    case "video": $allowedMime = array_merge($allowedMime, self::videoMimes()); break;
                    case "audio": $allowedMime = array_merge($allowedMime, self::audioMimes()); break;
                    case "file": $allowedMime = array_merge($allowedMime, self::fileMimes()); break;
                    case "image": $allowedMime = array_merge($allowedMime, self::imageMimes());
                }
            }
        }
        if($maxSize) $this->setMaxSize($maxSize);
        if($allowedMime) $this->setValidMimes($allowedMime);
    }



    public function isValid():bool{
        if(!$this->isExist()){
            $this->errors['file'] = "file not found";
            return false;
        }

        if(count($this->allowedMime) > 0 && !in_array($this->mime, $this->allowedMime)){
            $this->errors['validMimes'] = "selected file mime is not in allowed ones";
        }

        if($this->size > $this->allowedMaxSize){
            $this->errors['maxSize'] = "selected file size is bigger than " . round($this->allowedMaxSize / pow(1024, 1)) . " KB";
        }

        return count($this->errors) === 0;
    }

    public function setFilename(string $newName):void{
        $this->filename = $newName;
    }

    public function setFilenameWithTime():void{
        $this->setFilename($this->filename."_".time());
    }

    public function setValidMimes(array $mimes):void{
        $this->allowedMime = $mimes;
    }

    public function setMaxSize(string $size):void{
        $convertedSize = self::convertSize($size);
        if(!is_int($convertedSize)){
            throw new \Error("format of maxSize is not correct");
        }
        $this->allowedMaxSize = $convertedSize;
    }

    public function getErrors():array{
        return $this->errors;
    }


    public function isExist():bool{
        return $this->file !== null;
    }

    public function getName():string{
        return $this->filename;
    }

    public function getNameWithExtension():string{
        return $this->filename . "." . $this->extension;
    }

    public function getType():string{
        return $this->extension;
    }

    public function getMime():string{
        return $this->mime;
    }

    public function isImage():bool{
        return in_array($this->mime, self::imageMimes());
    }

    public function getSizeInK():float{
        return round($this->size / pow(1024, 1),1);
    }

    public function getSizeInM():float{
        return round($this->size / pow(1024, 2),1);
    }

    private static function convertSize(string $size):int{
        switch (substr($size, -1)) {
            case 'G': $sizeInByte = intval($size) * pow(1024, 3); break;
            case 'M': $sizeInByte = intval($size) * pow(1024, 2); break;
            case 'K': $sizeInByte = intval($size) * pow(1024, 1); break;
            default:  $sizeInByte = intval($size);                break;
        }
        return $sizeInByte;
    }

    private static function createPath($path):bool {
        if (is_dir($path)) return true;
        $prev_path = substr($path, 0, strrpos($path, '/', -2) + 1 );
        $return = self::createPath($prev_path);
        return ($return && is_writable($prev_path)) ? mkdir($path) : false;
    }

    private static function imageMimes():array{
        return ['image/bmp','image/bmp','image/jpeg','image/pipeg','image/svg+xml','image/tiff','image/x-icon','image/vnd.microsoft.icon'];
    }

    private static function videoMimes():array{
        return ['video/mpeg','video/mp4','video/x-msvideo','video/ogg','video/webm'];
    }

    private static function audioMimes():array{
        return ['audio/basic','audio/mid','audio/mpeg','audio/x-wav', 'audio/aac','audio/ogg','audio/wav','audio/webm',];
    }

    private static function fileMimes():array{
        return ['application/pdf','application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation','application/vnd.rar','application/zip','application/x-7z-compressed'];
    }
}