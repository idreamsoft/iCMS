<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class ArticleUserApp extends UserContentApp
{

    public function __construct()
    {
        parent::__construct();
    }
    public function API_manage()
    {
        return $this->display();
    }
    public function API_publish()
    {
        $id = Request::get('id');
        if ($id) {
            $id && list($article, $data, $articleData) = Article::data($id, null, User::$id);
        }

        $isMarkdown = (int)(iCMS::$config['article']['markdown'] || $article['markdown']);
        $once = time();
        $sign = Utils::sign(compact('isMarkdown', 'id', 'once'));

        if (is_array($articleData['bodyArray'])) {
            $articleData['body'] = implode(iPHP_PAGEBREAK.PHP_EOL.PHP_EOL, $articleData['bodyArray']);
        }
        if(!$isMarkdown){//ueditor
            $articleData['body'] = htmlspecialchars($articleData['body']);
        }

        View::assign('sign', $sign);
        View::assign('once', $once);
        View::assign('isMarkdown', $isMarkdown);

        View::assign('article', $article);
        View::assign('articleData', $articleData);
        return $this->display();
    }
    public function do_delete()
    {
        $id = (int) Request::post('id');
        $where = ['id' => $id, 'userid' => User::$id];

        DB::beginTransaction();
        try {
            Article::update(['status' => 2], $where);
            DB::commit();
        } catch (\sException $ex) {
            DB::rollBack();
            $msg = $ex->getMessage();
            iJson::error($msg);
        }
        iJson::success();
    }
    public function ACTION_save()
    {
        if (User::$config['post']['captcha']) {
            // Captcha::check() or iJson::error('iCMS:captcha:error');
        }
        if (User::$config['post']['interval']) {
            $last_postime = ArticleModel::where(['userid' => User::$id])->max('postime');
            if ($_SERVER['REQUEST_TIME'] - $last_postime < User::$config['post']['interval']) {
                iJson::error('user:publish:interval');
            }
        }
        $data = Request::post();

        $isMarkdown = (int) $data['isMarkdown'];
        $sign = Utils::sign(array(
            'isMarkdown' => $isMarkdown,
            'id' => (int) $data['id'],
            'once' => $data['once']
        ));
        if ($sign != $data['sign']) {
            iJson::error('iCMS:sign:error');
        }
        $data = array_filter_keys($data, 'id,cid,title,body,source,author,pic,tags,description');
        $data['markdown'] = $isMarkdown;

        if ($isMarkdown) {
            $body = $data['body'];
        } else {
            $body = Vendor::run('CleanHtml', array($_POST['body']));
        }
 
        $data['userid'] = User::$id;
        $data['author'] or $data['author'] = User::$nickname;
        $data['editor'] = User::$nickname;

        empty($data['title']) && iJson::error('user:publish:empty:title');
        empty($data['cid']) && iJson::error('user:publish:empty:cid');
        empty($body) && iJson::error('user:publish:empty:body');
        if ($data['pic']) {
            $tmparray = array("\0", "%00", '..');
            if (str_replace($tmparray, '', $data['pic']) != $data['pic']) {
                iJson::error('iCMS:file:invaild');
            }
            FilesClient::checkExt($data['pic']) or iJson::error('iCMS:file:failure');
        }
        array_walk_recursive($data, function (&$value, $key) {
            $fwd = iPHP::callback('Filter::run', array(&$value), false);
            $fwd && iJson::error('user:publish:filter_' . $key);
        });

        $data['pubdate'] = time();
        $data['postype'] = "0";

        $node = NodeCache::getId($data['cid']);
        $roleArray = $node['config']['role'];
        $data['status'] = UserCP::checkRole($roleArray['examine']) ? 3 : 1;


        DB::beginTransaction();
        try {
            if ($data['id']) {
                Article::update(
                    $data,
                    array('id' => $data['id'], 'userid' => User::$id)
                );
                ArticleDataModel::update(
                    array('body' => $body),
                    array('article_id' => $data['id'])
                );
            } else {
                $data['postime'] = time();
                $data['id'] = Article::create($data);
                ArticleDataModel::create(array(
                    'article_id' => $data['id'],
                    'body' => $body
                ));
            }
            DB::commit();
        } catch (\sException $ex) {
            DB::rollBack();
            $msg = $ex->getMessage();
            iJson::error($msg);
        }
        $lang = array(
            '1' => 'user:publish:success',
            '3' => 'user:publish:examine',
        );
        // $url = Route::routing('ArticleUser:publish');
        // $url = Route::make(['id'=>$data['id']],$url);
        $url = Route::routing('ArticleUser/manage');
        iJson::success($lang[$data['status']]);
    }
}
