<?php

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
            $out[] = $this->prepare_text($text, false);
        };
        $out[] = "</section>";

        $this->file_append($out);
    }

    public function add_file($id, $path) {
        $file_data = file_get_contents($path);
        $data = base64_encode($file_data);
        $out = Array('<binary id="' . $id . '" content-type="image/jpeg">' . $data . '</binary>');
        $this->file_append($out);
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
                    foreach ($result[4] as $_img){
                        $_img_urls[]=str_replace("'","",$_img);
                    }
                    preg_match_all('/\<img(.*)\>/isU', $text, $result2);
                    foreach ($result2[0] as $_pic){
                        $ok=false;
                        foreach ($_img_urls as $_img){
                           if (!$ok){
                               if (strpos($_pic, $_img)!==FALSE){
                                   $ok=true;
                                   $text=str_replace($_pic, '<image l:href="#'.$_img.'"/>', $text);
                               }
                           }
                        }
                    }
                }
            }
        }
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
        $tag.=$text;
        $tag.="</{$tag_name}>";

        return $tag;
    }

}
?>