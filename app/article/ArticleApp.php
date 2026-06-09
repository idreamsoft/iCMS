<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
//ArticleApp >> AppsAppTrait >> AppsBase
class ArticleApp extends AppsBase
{
    use AppsAppTrait;

    public function display($value, $field = 'id', $tpl = true)
    {
        $vars = ['tag'  => true, 'user' => true];
        $article = $this->getData($value, $field, $tpl);
        self::values($article, $vars, $tpl);
        self::body($article);
        self::getCustomData($article, $vars);
        return self::render($article, $tpl);
    }

    public static function values(&$article, $vars = [], $tpl = false)
    {
        self::initialize($article, $tpl);

        $article['statusText'] = ArticleModel::$statusMap[$article['status']];
        $article['postypeText'] = ArticleModel::$postypeMap[$article['postype']];

        $vars['tag'] && TagApp::getArray($article, $article['node']['name'], 'tags');

        AppsCommon::init($article, $vars)
            ->link()
            ->text2link()
            ->user()
            ->comment()
            ->pic()
            ->hits()
            ->params();

        return $article;
    }

    public static function body(&$article)
    {
        $dataModel = ArticleDataModel::sharding($article['id']);
        $pn = intval(self::$pageNum - 1);
        if ($article['chapter']) {
            $chapterData = $dataModel->field('id,subtitle')->where('article_id', $article['id'])->select();
            asort($chapterData);
            $count = count($chapterData);
            $adid = $chapterData[$pn]['id'];
            $data = $dataModel->get($adid);
            $article['body'] = $data['body'];
        } else {
            $data = $dataModel->where('article_id', $article['id'])->get();
            $body = $data['body'] ? explode(iPHP_PAGEBREAK, $data['body']) : [];
            $count = count($body);
            if($article['creative'] == Article::BODY_JSON_KEY){
                $article['body'] = json_decode($data['body'], true);
            }else{
                $article['body'] = $body[$pn];
            }
            unset($body);
        }
        if ($article['markdown'] && self::$config['markdown_parser']!==0) {
            $markdown = PluginMarkdown::parser($article['body']);
            $markdown && $article['body'] = $markdown;
        }
        $article['body_pics'] = FilesPic::findImgUrl($data['body'], $picArray);
        $article['subtitle'] = $data['subtitle'];
        $total = $count + intval(self::$config['pageno_incr']);
        Paging::content($article, self::$pageNum, $total, $count, $chapterData);
        self::bodyPicsPage($article, $picArray, self::$pageNum, $total);
    }

    //保留静态方法 articleFunc 中调用
    public static function data($idArray = [], $fields = null)
    {
        foreach ($idArray as $key => $article_id) {
            $sql = ArticleDataModel::sql($article_id);
            $sql && $sqlArray[] = $sql;
        }
        $unionAll = implode(' UNION ALL ', $sqlArray);
        $variable = ArticleDataModel::select($unionAll);
        foreach ($variable as $key => $value) {
            $data[$value['article_id']][] = $value;
        }
        return $data;
    }

    public static function bodyPicsPage(&$data, $picArray, $page, $total)
    {
        if (empty($picArray[0])) {
            return;
        }
        $imgArray = array_unique($picArray[0]);

        is_array($data['page']['next']) && $nextUrl = $data['page']['next']['url'];
        foreach ($imgArray as $key => $img) {
            if (!self::$config['img_title']) {
                $img = preg_replace('@title\s*=\s*(["\']?).*?\\1\s*@is', '', $img);
                $img = preg_replace('@alt\s*=\s*(["\']?).*?\\1\s*@is', '', $img);
                $img = str_replace('<img', '<img title="' . addslashes($data['title']) . '" alt="' . addslashes($data['title']) . '"', $img);
            }
            if (self::$config['pic_center']) {
                $imgReplace[$key] = '<p class="pic_center">' . $img . '</p>';
            } else {
                $imgReplace[$key] = $img;
            }
            if (self::$config['pic_next'] && $total > 1) {
                $clicknext = '<a href="' . $nextUrl . '"><b>' . Lang::get('msg:click_next') . ' (' . $page . '/' . $total . ')</b></a>';
                $clickimg = '<a href="' . $nextUrl . '" title="' . $data['title'] . '" class="img">' . $img . '</a>';
                if (self::$config['pic_center']) {
                    $imgReplace[$key] = '<p class="click2next">' . $clicknext . '</p>';
                    $imgReplace[$key] .= '<p class="pic_center">' . $clickimg . '</p>';
                } else {
                    $imgReplace[$key] = '<p>' . $clicknext . '</p>';
                    $imgReplace[$key] .= '<p>' . $clickimg . '</p>';
                }
            }
        }
        $data['body'] = str_replace($imgArray, $imgReplace, $data['body']);
    }
}
