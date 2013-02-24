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
                $out[] = $this->prepare_text("<p>".$line."</p>", false);
            }
        }
        $out[] = $this->prepare_text($text, false);
        $out[] = "</section>";

        $this->file_append($out);
    }

    public function add_file() {
        
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