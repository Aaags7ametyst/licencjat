<?php

namespace Model;

use Silex\Application;


class CommentsModel
{

    protected $_db;


    public function __construct(Application $app)
    {
        $this->_db = $app['db'];
    }


    public function getComment($idcomment)
    {
        $sql = 'SELECT * FROM lic_comments WHERE idcomment = ? LIMIT 1';
        return $this->_db->fetchAssoc($sql, array($idcomment));
    }


    public function getCommentsList($id)
    {
        $sql = 'SELECT * FROM lic_comments WHERE idpost = ?';
        return $this->_db->fetchAll($sql, array($id));
    }


    public function addComment($data)
    {
        $sql = 'INSERT INTO lic_comments
            (content, published, idpost, author)
            VALUES (?,?,?,?)';
        $this->_db
            ->executeQuery(
                $sql,
                array(
                    $data['content'],
                    $data['published'],
                    $data['idpost'],
                    $data['author']
                )
            );
    }


    public function editComment($data)
    {

        if (isset($data['idcomment'])
            && ctype_digit((string)$data['idcomment'])) {
            $sql = 'UPDATE lic_comments
                SET content = ?, published_date = ?, author = ?,
            WHERE idcomment = ?';
            $this->_db->executeQuery(
                $sql, array(
                    $data['content'],
                    $data['published'],
                    $data['idcomment'],
                    $data['author'],

                )
            );
        } else {
            $sql = 'INSERT INTO lic_comments
                (content, published_date, idpost, author)

            VALUES (?,?,?,?)';
            $this->_db
                ->executeQuery(
                    $sql,
                    array(
                        $data['content'],
                        $data['published'],
                        $data['idpost'],
                        $data['author'],
                       // $data['idCurrentUser']
                    )
                );
        }
    }


    public function deleteComment($data)
    {
        $sql = 'DELETE FROM `lic_comments` WHERE `idcomment`= ?';
        $this->_db->executeQuery($sql, array($data['idcomment']));
    }


    public function checkCommentId($idcomment)
    {
        $sql = 'SELECT * FROM lic_comments WHERE idcomment=?';
        $result = $this->_db->fetchAll($sql, array($idcomment));

        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}