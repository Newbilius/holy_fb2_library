<?php
include_once("strip_tags_smart/strip_tags_smart.php");

function loadImageByType($filename, $type) {
    switch ($type) {
        case "image/gif":
            return @imagecreatefromgif($filename);
        case "image/jpeg":
            return @imagecreatefromjpeg($filename);
        case "image/png":
            return @imagecreatefrompng($filename);
        default:
            return false;
    }
}

;

class HolyFB2 {

    protected $file_name = "";
    protected $default_header = array("encoding" => "utf-8",
        "genre" => array("computers"),
        "author" => Array(
            "first-name" => "",
            "last-name" => "",
            "middle-name" => "",
            "nickname" => "",
        ),
        "document_author" => Array(
            "first-name" => "",
            "last-name" => "",
            "middle-name" => "",
            "nickname" => "",
        ),
        "book-title" => "un_named",
        "annotation" => "",
        "lang" => "ru",
        "version" => "1.0",
        "date" => "",
        "id" => "",
    );

    function HolyFB2($file_name) {
        $this->set_file($file_name);
    }

    public function set_file($file_name) {
        $this->file_name = $file_name;
    }

    protected function _get_document_info($data, $out) {
        $out[] = '<document-info>';

        $out[] = '<author>';
        foreach ($data['document_author'] as $name => $_item) {
            if ($_item) {
                $out[] = $this->prepare_tag($name, $_item);
            }
        };
        $out[] = '</author>';

        if ($data['date']) {
            $date = date("Y-m-d", strtotime($data['date']));
        } else {
            $date = date("Y-m-d");
            $data['date'] = $date;
        }
        $out[] = $this->prepare_tag("date", $data['date'], array("value" => $date));

        if (!$data['id']) {
            $data['id'] = date("Y-m-d_H_i_s ") . MD5(date("Y-m-d_H_i_s"));
        }

        $out[] = $this->prepare_tag("id", $data['id']);

        $out[] = '</document-info>';

        return $out;
    }

    protected function _get_title_info($data, $out) {
        $out[] = '<title-info>';

        foreach ($data['genre'] as $_tag) {
            $out[] = $this->prepare_tag("genre", $_tag);
        };

        $out[] = '<author>';
        foreach ($data['author'] as $name => $_item) {
            if ($_item) {
                $out[] = $this->prepare_tag($name, $_item);
            }
        };
        $out[] = '</author>';

        $out[] = $this->prepare_tag("book-title", $data['title']);
        $out[] = $this->prepare_tag("lang", $data['lang']);
        if ($data['annotation']) {
            $out[] = $this->prepare_tag("annotation", $this->prepare_text($data['annotation']));
        }

        $out[] = '</title-info>';

        return $out;
    }

    public function write_header($data) {
        $data = array_merge($this->default_header, $data);
        $out = array();

        $out[] = '<?xml version="1.0" encoding="' . $data['encoding'] . '"?>';

        //@todo выяснить, почему gribuser.ru
        $out[] = '<FictionBook xmlns="http://www.gribuser.ru/xml/fictionbook/2.0" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:l="http://www.w3.org/1999/xlink">';

        $out[] = '<description>';

        $out = $this->_get_title_info($data, $out);

        $out = $this->_get_document_info($data, $out);

        $out[] = '</description>';

        $this->file_append($out, true);
    }

    public function write_footer() {
        $this->file_append(Array("</FictionBook>"));
    }

    public function write_start_body() {
        $this->file_append(Array("<body>"));
    }

    public function write_end_body() {
        $this->file_append(Array("</body>"));
    }

    public function add_section($title, $text) {
        $out = array();

        $out[] = "<section>";
        $out[] = $this->prepare_tag("title", "<p>" . $title . "</p>");
        if (is_array($text)) {
            foreach ($text as $line) {
                $out[] = $this->prepare_text("<p>" . $line . "</p>", false);
            }
        } else {
            $out[] = $this->prepare_text("<p>" . $text . "</p>", false);
        };
        $out[] = "</section>";

        $this->file_append($out);
    }
    
    private function _resize_img($source,$width_old,$height_old,$max_size) {
        $width=$max_size;
        $height=$max_size;
        if (($width_old == 0) || ($height_old == 0)) {
            $width_old = imagesx($source);
            $height_old = imagesy($source);
        }
        if ($width == 0)
            $width = $width_old;
        if ($height == 0)
            $height = $height_old;
        $new_width = $width;
        $new_height = $height;

        if ($new_width == 0) {
            //жмем по известной высоте
            $width = $width_old * $height / $height_old;
        } elseif ($new_height == 0) {
            //жмем по известной ширине
            $height = $height_old * $width / $width_old;
        } else {
            //подгоняем к известной ширине и высоте
            if ($height_old > $width_old) {
                $width = $width_old * $height / $height_old;
                if ($width > $width) {
                    $width = $width;
                    $height = $height_old * $width / $width_old;
                }else
                    $height = $height;
            }else {
                $height = $height_old * $width / $width_old;
                if ($height > $height) {
                    $height = $height;
                    $width = $width_old * $height / $height_old;
                }else
                    $width = $width;
            }
        };

        $width = intval($width);
        $height = intval($height);
        $new_img = @imagecreatetruecolor($width, $height);

        if ($new_img) {
            $source_old = $source;
            imageAlphaBlending($new_img, false);
            imageSaveAlpha($new_img, true);
            imagecopyresampled($new_img, $source, 0, 0, 0, 0, $width, $height, $width_old, $height_old);
            $source = $new_img;
        }
        return $source;
    }
    
    public function add_file($id, $path,$max_size=999999) {
        $img_size = getimagesize($path);
        $mime = $img_size['mime'];
        $width = $img_size[0];
        $height = $img_size[1];
        $delete_after_complete = false;

        if (($mime != "image/jpeg" && $mime != "image/png") || ($width>$max_size || $height>$max_size)) {
            $source = loadImageByType($path, $mime);
            if ($source) {
                $source=$this->_resize_img($source,$width,$height,$max_size);
                imagejpeg($source, "_tmp.jpg");
                $mime = "image/jpeg";
                $path = "_tmp.jpg";
                $delete_after_complete = true;
            } else {
                $path = "";
            }
        };

        if ($path) {
            if (file_exists($path)) {
                $file_data = file_get_contents($path);
                $data = base64_encode($file_data);
                $out = Array('<binary id="' . $id . '" content-type="' . $mime . '">' . $data . '</binary>');
                $this->file_append($out);
            };
        };

        if ($delete_after_complete) {
            if (file_exists("_tmp.jpg")) {
                unlink("_tmp.jpg");
            }
        }
    }

    protected function file_append($data, $create = false) {
        if (is_array($data)) {
            $data = implode("\r\n", $data);
        };
        if ($create) {
            file_put_contents($this->file_name, $data);
        } else {
            file_put_contents($this->file_name, "\r\n" . $data, FILE_APPEND | LOCK_EX);
        }
    }

    protected function prepare_text($text, $clear_img = true) {
        if (!$clear_img) {
            preg_match_all('/\<(.*)img(.*)src(.*)=(.*)\>/isU', $text, $result);
            if (is_array($result[4])) {
                if (count($result[4]) > 0) {
                    foreach ($result[4] as $_img) {
                        $_img_urls[] = str_replace(Array("'", '"'), "", $_img); //@todo заменить на более полный фильтр
                    }
                    preg_match_all('/\<img(.*)\>/isU', $text, $result2);
                    foreach ($result2[0] as $_pic) {
                        $ok = false;
                        foreach ($_img_urls as $_img) {
                            if (!$ok) {
                                if (strpos($_pic, $_img) !== FALSE) {
                                    $ok = true;
                                    $text = str_replace($_pic, '<image l:href="#' . $_img . '"/>', $text);
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($clear_img) {
            $text = strip_tags_smart($text, Array("<p>","<strong>","<emphasis>","<i>","<b>","<br>"));
        } else {
            $text = strip_tags_smart($text, Array("<p>","<strong>","<emphasis>","image","<i>","<b>","<br>"));
        };
        $text = str_replace(Array("<br>", "</br>"), "</p><p>", $text);
        $text = str_replace(Array("<i>", "</i>"), Array("<emphasis>", "</emphasis>"), $text);
        $text = str_replace(Array("<b>", "</b>"), Array("<strong>", "</strong>"), $text);
        $text = str_replace(Array("\r\n","\r","\n"), "", $text);
        return $text;
    }

    protected function prepare_tag($tag_name, $text, $params = array()) {
        $tag = "<{$tag_name}";

        if (count($params) > 0) {
            foreach ($params as $code => $_param) {
                $tag.=' ' . $code . '="' . $_param . '"';
            }
        }

        $tag.=">";
        $tag.=$this->prepare_text($text);
        $tag.="</{$tag_name}>";

        return $tag;
    }

}

?>