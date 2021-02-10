<?php require_once("../../../wp-load.php");

// dependencies
$res = [];
$tzLocation = '';
$onlytagsallowed = [];
$template = '';
$tagtemplate = '';
$groupbyyear = false;
$skipcustomtags = false;
$showpreview = false;
$previewlen = 0;
$timestampFormat = '';

$years = [];
$group_by_year = $groupbyyear && !empty($res);

foreach (json_decode(stripslashes($_POST['res'])) as $k => $item) {

    $year = explode("-", $item->post_date_gmt)[0];
    if ($k === 0) {
        $years[] = $year;

        if ($group_by_year) {
            $this->parse_year_header($year);
        }
    } else if (!in_array($year, $years, true)) {
        if ($group_by_year) {
            $this->parse_year_header($year);
        }
        $years[] = $year;
    }

    $date = new DateTime($item->post_date_gmt . "Z");
    $date->setTimezone(new DateTimeZone($_POST['tzLocation']));
    $datestr = date_i18n($_POST['timestampFormat'],$date->getTimestamp() + $date->getOffset());

    $tags = "";
    foreach ($item->tags as $tag) {
        $parts = explode(":", $tag);
        if (count($parts) < 2 || strlen($parts[1]) === 2) {
            continue;
        }
        if ($skipcustomtags && strpos($parts[0], 'mfn-cus-') !== false) {
            continue;
        }
        // always skip 'pll_' slug tags
        if (strpos($parts[0], 'pll_') !== false) {
            continue;
        }
        if (count($onlytagsallowed) > 0) {
            $base_tag = explode("_", $parts[1])[0];
            $key = array_search($base_tag, $onlytagsallowed, true);
            if (!is_numeric($key)) {
                continue;
            }
        }
        $html = base64_decode($_POST['tagtemplate']);
        $html = str_replace(array("{{tag}}", "{{slug}}"), array($parts[0], $parts[1]), $html);
        $html = str_replace(array("[tag]", "[slug]"), array($parts[0], $parts[1]), $html);
        $tags .= $html;
    }

    $item_url = $_POST['item_url'] . $item->post_name;

    $templateData = array(
        'date' => $datestr,
        'title' => $item->post_title,
        'url' => $item_url,
        'tags' => $tags,
    );

    $templateData['preview'] = '';

    if ($showpreview) {
        $dom = new DomDocument();
        $encoding = '<?xml encoding="utf-8" ?>';
        $post_content = str_replace(array('<br/>', '<br>'), ' ', $item->post_content);

        $appendEllipsis = false;
        @$dom->loadHTML($encoding . $post_content);
        $preview = '';

        foreach ($dom->getElementsByTagName('p') as $node) {
            if (!$node->textContent) {
                continue;
            }
            $value = str_replace('&nbsp;', ' ', htmlentities($node->textContent));
            if (trim($value) === '') {
                continue;
            }
            $preview .= trim($value) . ' ';
            if ($previewlen !== '' && strlen($preview) > $previewlen) {
                $appendEllipsis = true;
                break;
            }
        }

        if ($previewlen !== '') {
            $words = explode(' ', $preview);
            $preview = '';
            foreach ($words as $word) {
                $preview .= $word . ' ';
                if (strlen($preview) > $previewlen) {
                    $appendEllipsis = true;
                    break;
                }
            }
        }

        $preview = rtrim($preview);

        if ($appendEllipsis) {
            $preview = rtrim($preview, '.,:;!');
            $preview .= '<span class="mfn-ellipsis">...</span>';
        }

        $templateData['preview'] = $preview;
    }

    $html = base64_decode($_POST['template']);

    foreach ($templateData as $key => $value) {
        $html = str_replace("[$key]", $value, $html);
        $html = str_replace("{{" . $key . "}}", $value, $html);
    }

    echo $html;
}
