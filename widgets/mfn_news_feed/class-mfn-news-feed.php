<?php
class News_feed {

    private function parse_year_header($year): string
    {
        return "<h4 class='mfn-feed-year-header' id='mfn-feed-year-header-" . $year . "'>$year</h4>";
    }

    public function queryParam($name, $default) {
        return $_GET[$name] ?? $default;
    }

    public function handleTags()
    {

        $hasTags = array();
        $hasNotTags = array();

        foreach (explode(",", $this->queryParam('m-tags', "")) as $tag) {
            if (empty($tag)) {
                continue;
            }

            if (strpos($tag, '-') === 0 || strpos($tag, '!') === 0) {
                $tag = substr($tag, 1);
                if (strpos($tag, 'mfn-') !== 0) {
                    $tag = 'mfn-' . $tag;
                }

                $hasNotTags[] = $tag;
                continue;
            }

            if (strpos($tag, 'mfn-') !== 0) {
                $tag = 'mfn-' . $tag;
            }

            $hasTags[] = $tag;
        }

        return array(
            'hasTags' => $hasTags,
            'hasNotTags' => $hasNotTags,
        );
    }

    public function list_news_items($feed, $tzLocation, $timestampFormat, $onlytagsallowed, $tagtemplate, $template, $groupbyyear, $skipcustomtags, $showpreview, $previewlen, $disclaimerurl, $disclaimertag) {

        $result = '';
        $years = [];
        $group_by_year = $groupbyyear && !empty($feed);

        foreach ($feed as $k => $item) {

            $year = explode("-", $item->post_date_gmt)[0];
            if ($k === 0) {
                $years[] = $year;

                if ($group_by_year) {
                    $result .= $this->parse_year_header($year);
                }
            } else if (!in_array($year, $years, true)) {
                if ($group_by_year) {
                    $result .= $this->parse_year_header($year);
                }
                $years[] = $year;
            }

            $date = new DateTime($item->post_date_gmt . "Z");

            try {
                $date->setTimezone(new DateTimeZone($tzLocation));
            } catch(Exception $e) {
                echo $e->getMessage();
            }

            $datestr = date_i18n($timestampFormat,$date->getTimestamp() + $date->getOffset());

            $tags = "";
            $is_disclaimer = false;
            foreach ($item->tags as $tag) {
                $parts = explode(":", $tag);
                if (count($parts) < 2 || strlen($parts[1]) === 2) {
                    continue;
                }
                if ($disclaimertag) {
                    $base_tag = explode("_", $parts[1])[0];
                    if ($base_tag === $disclaimertag && strpos($parts[0], 'pll_') === false) {
                        $is_disclaimer = true;
                    }
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
                $html = $tagtemplate;
                $html = str_replace(array("{{tag}}", "{{slug}}"), array($parts[0], $parts[1]), $html);
                $html = str_replace(array("[tag]", "[slug]"), array($parts[0], $parts[1]), $html);
                $tags .= $html;
            }

            $item_url = get_permalink($item->post_id);

            if ($is_disclaimer) {
                $name_query_param = strpos($disclaimerurl, '?') === false ? '?' : '&';
                $name_query_param .= 'post-name=' . $item->post_name;
                $item_url = $disclaimerurl . $name_query_param;
            }

            $templateData = array(
                'date' => $datestr,
                'title' => $item->post_title,
                'url' => $item_url,
                'tags' => $tags,
                'year' => $year
            );

            $templateData['preview'] = '';

            if ($showpreview && isset($item->post_content)) {

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

                // append ellipsis
                if ($appendEllipsis) {
                    $preview = rtrim($preview, '.,:;!');
                    $preview .= '<span class="mfn-ellipsis">...</span>';
                }

               $templateData['preview'] = $preview;
            }

            $html = $template;
            foreach ($templateData as $key => $value) {
                $html = str_replace("[$key]", $value, $html);
                $html = str_replace("{{" . $key . "}}", $value, $html);
            }

            $result .= $html;

        }

        return $result;
    }
}
