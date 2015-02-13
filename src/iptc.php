<?php
/*
Examples taken from php.net
*/

DEFINE('IPTC_OBJECT_NAME', '005');
DEFINE('IPTC_EDIT_STATUS', '007');
DEFINE('IPTC_PRIORITY', '010');
DEFINE('IPTC_CATEGORY', '015');
DEFINE('IPTC_SUPPLEMENTAL_CATEGORY', '020');
DEFINE('IPTC_FIXTURE_IDENTIFIER', '022');
DEFINE('IPTC_KEYWORDS', '025');
DEFINE('IPTC_RELEASE_DATE', '030');
DEFINE('IPTC_RELEASE_TIME', '035');
DEFINE('IPTC_SPECIAL_INSTRUCTIONS', '040');
DEFINE('IPTC_REFERENCE_SERVICE', '045');
DEFINE('IPTC_REFERENCE_DATE', '047');
DEFINE('IPTC_REFERENCE_NUMBER', '050');
DEFINE('IPTC_CREATED_DATE', '055');
DEFINE('IPTC_CREATED_TIME', '060');
DEFINE('IPTC_ORIGINATING_PROGRAM', '065');
DEFINE('IPTC_PROGRAM_VERSION', '070');
DEFINE('IPTC_OBJECT_CYCLE', '075');
DEFINE('IPTC_BYLINE', '080');
DEFINE('IPTC_BYLINE_TITLE', '085');
DEFINE('IPTC_CITY', '090');
DEFINE('IPTC_PROVINCE_STATE', '095');
DEFINE('IPTC_COUNTRY_CODE', '100');
DEFINE('IPTC_COUNTRY', '101');
DEFINE('IPTC_ORIGINAL_TRANSMISSION_REFERENCE',     '103');
DEFINE('IPTC_HEADLINE', '105');
DEFINE('IPTC_CREDIT', '110');
DEFINE('IPTC_SOURCE', '115');
DEFINE('IPTC_COPYRIGHT_STRING', '116');
DEFINE('IPTC_CAPTION', '120');
DEFINE('IPTC_LOCAL_CAPTION', '121');

class iptc {
    var $meta=Array();
    var $hasmeta=false;
    var $file=false;
    var $path=false;


    public function __construct ($filename) {
        $this->path = $filename;
//        echo 'IPTC Loading for: '.$filename.'<br />';
        $size = getimagesize($filename,$info);
        $this->hasmeta = isset($info["APP13"]);
        if($this->hasmeta)
            $this->meta = iptcparse($info["APP13"]);

//        print_r($this->meta);

        $this->file = $filename;
    }

    function set($tag, $data) {
//        echo 'Updating IPTC Tag.<br />';
        $this->meta ["2#$tag"]= $data;
        $this->hasmeta=true;


//        print_r($this->meta);
    }

    function get($tag) {
//        echo 'Getting IPTC data.<br />';
        return isset($this->meta["2#$tag"]) ? $this->meta["2#$tag"][0] : false;
    }

    function view() {
//        echo 'Print IPTC Data.<br />';

        foreach(array_keys($this->meta) as $s) {
            $c = count ($this->meta[$s]);
            for ($i=0; $i <$c; $i++)
            {
                echo $s.' = '.$this->meta[$s][$i].'<br />';
            }
        }
    }

    function binary() {
//        echo 'Setting new binary block for IPTC writing.<br />';


        $iptc = '';
        foreach (array_keys($this->meta) as $key) {

            $tag   = str_replace("2#", "", $key);

            foreach($this->meta[$key] as $value) {
                $iptc .= $this->iptc_maketag(2, $tag, $value);
            }
        }
        return $iptc;

//        $iptc_new = '';
//
//        foreach (array_keys($this->meta) as $s) {
//            $c = count ($this->meta[$s]);
//            for ($i=0; $i <$c; $i++)
//            {
//                $tag = str_replace("2#", "", $s);
//                $iptc_new .= $this->iptc_maketag(2, $tag, $this->meta[$s][$i]);
//            }
//        }
//        return $iptc_new;

    }

    function iptc_maketag($rec,$dat,$val) {

        if (is_array($val)) {
            $source = '';
            foreach($val as $item) {
                $len = strlen($item);
                $source .= chr(0x1c).chr($rec).chr($dat);
                $source .= chr($len >> 8).
                    chr($len & 0xff).
                    $item;
            }

        } else {
            $source = '';
            $len = strlen($val);
            $source .= chr(0x1c).chr($rec).chr($dat);
            $source .= chr($len >> 8).
                chr($len & 0xff).
                $val;
        }
        return $source;
//        echo 'Making IPTC Tag<br />';
//
//        $len = strlen($val);
//        if ($len < 0x8000) {
//            return chr(0x1c).chr($rec).chr($dat).
//            chr($len >> 8).
//            chr($len & 0xff).
//            $val;
//        } else {
//            return chr(0x1c).chr($rec).chr($dat).
//            chr(0x80).chr(0x04).
//            chr(($len >> 24) & 0xff).
//            chr(($len >> 16) & 0xff).
//            chr(($len >> 8 ) & 0xff).
//            chr(($len ) & 0xff).
//            $val;
//        }
    }
    function write() {
//        echo 'Writing file...<br />';
        if(!function_exists('iptcembed')) return false;
        $mode = 0;
//        var_dump($this->binary());
//        var_dump($this->path);
        $content = iptcembed($this->binary(), $this->path, $mode);
        $filename = $this->file;

        @unlink($filename); #delete if exists

        $fp = fopen($filename, "w");
        fwrite($fp, $content);
        fclose($fp);
    }

    #requires GD library installed
    function removeAllTags() {
//        'Removing previous IPTC tags to re-write new data.<br />';
        $this->hasmeta=false;
        $this->meta=Array();
        $img = imagecreatefromstring(implode(file($this->file)));
        @unlink($this->file); #delete if exists
        imagejpeg($img,$this->file,100);
    }
};
?>