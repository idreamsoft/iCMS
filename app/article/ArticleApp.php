<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class ArticleApp extends AppsApp
{
    public function __construct()
    {
        parent::__construct('article');
    }
    public function display($value, $field = 'id', $tpl = true)
    {
        try {
            $vars = ['tag'  => true, 'user' => true];
            $article = $this->getData($value, $field);
            self::values($article, $vars, $tpl);
            self::body($article);
            self::getCustomData($article, $vars);
            self::hooked($article);
            return self::render($article, $tpl);
        } catch (\FalseEx $fex) {
            return false;
        }
    }

    public static function values(&$article, $vars = array(), $tpl = false)
    {
        self::initialize($article, $tpl);

        $article['statusText']  = Article::$statusMap[$article['status']];
        $article['postypeText'] = Article::$postypeMap[$article['postype']];

        $vars['tag'] && TagApp::getArray($article, $article['node']['name'], 'tags');

        AppsCommon::init($article, $vars)
            ->link()
            ->text2link()
            ->user()
            ->comment()
            ->pic()
            ->hits()
            ->param();

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
            $body = explode(iPHP_PAGEBREAK, $data['body']);
            $count = count($body);
            $article['body'] = $body[$pn];
            unset($body);
        }
        if ($article['markdown']) {
            $markdown = PluginMarkdown::parser($article['body']);
            $markdown && $article['body'] = $markdown;
        }
        $article['pics'] = FilesPic::findImgUrl($data['body'], $picArray);
        $article['subtitle'] = $data['subtitle'];
        $total = $count + intval(self::$config['pageno_incr']);
        Paging::content($article, self::$pageNum, $total, $count, $chapterData);
        self::bodyPicsPage($article, $picArray, self::$pageNum, $total);
    }
    //保留静态方法 articleFunc 中调用
    public static function data($idArray = [], $fields = null)
    {
        foreach ($idArray as $key => $article_id) {
            $sql = ArticleDataModel::get_sql($article_id);
            $sql && $sqlArray[] = $sql;
        }
        $unionAll = implode(' UNION ALL ', $sqlArray);
        $variable = ArticleDataModel::select($unionAll);
        foreach ($variable as $key => $value) {
            $data[$value['article_id']][] = $value;
        }
        return $data;
    }
}
