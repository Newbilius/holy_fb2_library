<?

function print_pr($data) {
    echo "<pre>" . print_r($data, true) . "</pre>";
}

header('Content-Type: text/html; charset=utf-8');
include_once dirname(dirname(__FILE__)) . "/holy_fb2.php";

$file = new HolyFB2("test.fb2");

$file->write_header(Array(
));
$file->write_start_body();

$file->add_section("Глава 1", "Текст первой главы. Пока - просто текст.");
$file->add_section("Глава 2", Array("Текст второй главы. Уже лучше...",
    "Картинка тут будет:<img src='koala_test.jpg'>",
    "<p>А тут у нас будет вторая картинка - сконвертированная из GIF</p>
        <p>картинка <img src='gif_img.gif'></p>"));

$file->write_end_body();

$file->add_file("koala_test.jpg", "koala_test.jpg");
$file->add_file("gif_img.gif", "gif_img.gif");

$file->write_footer();