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
    public function api_manage()
    {
        return $this->display();
    }

    public function api_publish()
    {
        $config = User::$config['post'];
        $id = (int)Request::get('id');
        $id && list($article, $data, $articleData) = Article::data($id, null, User::$id);
        $isMarkdown = (int)($config['markdown'] || iCMS::$config['article']['markdown'] || $article['markdown']);
        $once = time();
        $sign = Utils::sign(compact('isMarkdown', 'id', 'once'));

        if (is_array($articleData['bodyArray'])) {
            $articleData['body'] = implode(iPHP_PAGEBREAK . PHP_EOL . PHP_EOL, $articleData['bodyArray']);
        }
        if (!$isMarkdown) {//ueditor
            $articleData['body'] && $articleData['body'] = htmlspecialchars($articleData['body']);
        }

        View::assign('editor', $config['editor'] ?: iCMS::$config['article']['editor']);
        View::assign('sign', $sign);
        View::assign('once', $once);
        View::assign('isMarkdown', $isMarkdown);

        View::assign('article', $article);
        View::assign('articleData', $articleData);
        return $this->display();
    }

    /**
     * 删除文章
     *
     * @return void
     */
    public function api_delete()
    {
        // if ($id_hash = Request::post('id')) {
        //     $id = Crypt::decrypt($id_hash);
        // }
        // $id or AppsBase::alert('errors:cryptId');
        $id = (int)Request::post('id');
        $id or AppsBase::alert('errors:empty:id');
        $where = ['id' => $id, 'userid' => User::$id];
        return (bool)Article::update(['status' => 2], $where);
    }

    public function done_save()
    {
        $config = User::$config['post'];
        if ($config['captcha']) {
            Captcha::check();
        }
        if ($config['interval']) {
            $last_postime = ArticleModel::where(['userid' => User::$id])->max('postime');
            if ((int)$_SERVER['REQUEST_TIME'] - (int)$last_postime < (int)$config['interval']) {
                AppsBase::alert('user:publish:interval');
            }
        }
        $data = Request::post();

        $isMarkdown = (int) $data['isMarkdown'];
        $sign = Utils::sign([
            'isMarkdown' => $isMarkdown,
            'id'         => (int) $data['id'],
            'once'       => $data['once']
        ]);
        if ($sign != $data['sign']) {
            iJson::error('errors:sign');
        }
        $data = array_filter_keys($data, 'id,cid,ucid,title,body,source,author,pic,tags,description');
        $data['markdown'] = $isMarkdown;

        if ($isMarkdown) {
            $body = $data['body'];
        } else {
            $body = Vendor::run('CleanHtml', [$_POST['body']]);
        }

        $data['userid'] = User::$id;
        $data['author'] or $data['author'] = User::$nickname;
        $data['editor'] = User::$nickname;

        empty($data['title']) && iJson::error('user:publish:empty:title');
        empty($data['cid']) && iJson::error('user:publish:empty:cid');
        empty($body) && iJson::error('user:publish:empty:body');
        if ($data['pic']) {
            $tmparray = ["\0", '%00', '..'];
            if (str_replace($tmparray, '', $data['pic']) != $data['pic']) {
                iJson::error('errors:invalid_file');
            }
            FilesClient::checkExt($data['pic']) or iJson::error('errors:illegal_file_type');
        }
        array_walk_recursive($data, function (&$value, $key) {
            $fwd = iPHP::callback('Filter::run', [&$value], false);
            $fwd && iJson::error('user:publish:filter_' . $key);
        });

        $data['pubdate'] = time();
        $data['postype'] = '0';

        $node = NodeCache::getId($data['cid']);
        $roleArray = $node['config']['role'];
        $data['status'] = UserCenter::checkRole($roleArray['examine']) ? 3 : 1;

        if ($data['id']) {
            Article::update(
                $data,
                ['id' => $data['id'], 'userid' => User::$id]
            );
            ArticleDataModel::update(
                ['body' => $body],
                ['article_id' => $data['id']]
            );
        } else {
            $data['postime'] = time();
            $data['id'] = Article::create($data);
            ArticleDataModel::create([
                'article_id' => $data['id'],
                'body'       => $body
            ]);
        }

        $msgMap = [
            '1' => 'user:publish:success',
            '3' => 'user:publish:examine',
        ];
        $url = Route::create('ArticleUser/manage');
        return [true, $msgMap[$data['status']], $url];
    }
}
