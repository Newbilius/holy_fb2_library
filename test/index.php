<?

header('Content-Type: text/html; charset=utf-8');
include_once dirname(dirname(__FILE__)) . "/holy_fb2.php";

$file=new HolyFB2("test.fb2");

$file->write_header(Array(
    
));
$file->write_start_body();

$file->add_section("Глава 1", Array("Текст первой главы. Пока - просто текст."));
$file->add_section("Глава 2", Array("Текст второй главы. Уже лучше..."));

$file->write_end_body();
$file->write_footer();