<?php

namespace Model;

use Silex\Application;

class PostsModel
{
    protected $_db;

    public function __construct(Application $app)
    {
        $this->_db = $app['db'];
    }


    public function getPost($idpost)
    {
        $sql = 'SELECT * FROM lic_posts WHERE idpost = ? LIMIT 1';
        return $this->_db->fetchAssoc($sql, array($idpost));
    }

    public function getPostList()
    {
        $sql = 'SELECT * FROM lic_posts natural join lic_categories
                WHERE lic_posts.idcategory=lic_categories.idcategory
                ORDER BY published;';

        return $this->_db->fetchAll($sql);
    }



    public function addPost($data)
    {
        $sql = 'INSERT INTO lic_posts
            (title, content, published, idcategory)
            VALUES (?,?,?,?)';
        $this->_db
            ->executeQuery(
                $sql,
                array(
                    $data['title'],
                    $data['content'],
                    $data['published'],
                    $data['category']
                )
            );
    }

    public function editPost($data)
    {

        if (isset($data['id']) && ctype_digit((string)$data['id'])) {
            $sql = 'UPDATE lic_posts
                    SET title = ?, content = ?, idcategory = ?
                    WHERE idpost = ?';
            $this->_db
                ->executeQuery(
                    $sql,
                    array(
                        $data['title'],
                        $data['content'],
                        $data['category'],
                        $data['id']
                    )
                );
        } else {
            $sql = 'INSERT INTO lic_posts
                    (title, content, published, idcategory)
                    VALUES (?,?,?,?)';
            $this->_db
                ->executeQuery(
                    $sql,
                    array(
                        $data['title'],
                        $data['content'],
                        $data['published'],
                        $data['category']
                    )
                );
        }
    }


    public function deletePost($data)
    {
        $post = 'DELETE FROM `lic_posts` WHERE `idpost`= ?';
        $this->_db->executeQuery($post, array($data['idpost']));

        $comments = 'DELETE FROM `lic_comments` WHERE `idpost`= ?';
        $this->_db->executeQuery($comments, array($data['idpost']));

    }


    public function countPostsPages($limit)
    {
        $pagesCount = 0;
        $sql = 'SELECT COUNT(*) as pages_count FROM lic_posts';
        $result = $this->_db->fetchAssoc($sql);
        if ($result) {
            $pagesCount = ceil($result['pages_count'] / $limit);
        }
        return $pagesCount;
    }

    public function getPostsPage($page, $limit, $pagesCount)
    {
        if (($page <= 1) || ($page > $pagesCount)) {
            $page = 1;
        }
        $sql = 'SELECT *
                FROM lic_posts
                natural join lic_categories
                ORDER BY idpost DESC
                LIMIT :start, :limit';
        $statement = $this->_db->prepare($sql);
        $statement->bindValue('start', ($page - 1) * $limit, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function getPostWithCategoryName($idpost)
    {
        $sql = 'SELECT *
                FROM lic_posts
                natural join lic_categories
                where idpost = ?
                LIMIT 1';

        return $this->_db->fetchAssoc($sql, array($idpost));
    }

    public function checkPostId($idpost)
    {
        $sql = 'SELECT * FROM lic_posts WHERE idpost=?';
        $result = $this->_db->fetchAll($sql, array($idpost));

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

}