<?php

namespace Model;

use Silex\Application;

class CategoriesModel
{

    protected $_db;


    public function __construct(Application $app)
    {
        $this->_db = $app['db'];
    }


    public function getCategory($idCategory)
    {
        $sql = 'SELECT * FROM lic_categories WHERE idcategory = ? LIMIT 1';
        return $this->_db->fetchAssoc($sql, array($idCategory));
    }

    public function getPostsListByIdcategory($id)
    {
        $sql = 'SELECT *
            FROM lic_posts
            natural join lic_categories
            where idcategory = ?';
        return $this->_db->fetchAll($sql, array($id));
    }

    public function addCategory($data)
    {
        $sql = 'INSERT INTO lic_categories (name) VALUES (?)';
        $this->_db->executeQuery($sql, array($data['name']));
    }

    public function getCategoriesDict()
    {
        $categories = $this->getCategories();
        $data = array();
        foreach ($categories as $row) {
            $data[$row['idcategory']] = $row['name'];
        }
        return $data;
    }

    public function getCategories()
    {
        $sql = 'SELECT * FROM lic_categories';
        return $this->_db->fetchAll($sql);
    }

    public function checkCategoryId($idcategory)
    {
        $sql = 'SELECT * FROM lic_categories WHERE idcategory=?';
        $result = $this->_db->fetchAll($sql, array($idcategory));

        if ($result) {
            return true;
        } else {
            return false;
        }
    }


}