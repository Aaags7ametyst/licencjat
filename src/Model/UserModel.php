<?php
/**
 * UserModel
 *
 * PHP version 5
 *
 * @category Model
 * @package  Model
 * @author  Agnieszka Gorgolewska <agnieszka.gorgolewska@uj.edu.pl>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link wierzba.wzks.uj.edu.pl
 */
namespace Model;

use Doctrine\DBAL\DBALException;
use Silex\Application;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * Class UserModel
 *
 * @category Model
 * @package  Model
 * @author   Agnieszka Gorgolewska <agnieszka.gorgolewska@uj.edu.pl>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     wierzba.wzks.uj.edu.pl/~12_gorgolewska
 * @uses Doctrine\DBAL\DBALException
 * @uses Silex\Application
 */
class UserModel
{
    /**
     * Silex application object
     *
     * @access protected
     * @var $_app
     */
    protected $_app;
    /**
     * Database access object.
     *
     * @access protected
     * @var $_db
     */
    protected $_db;

    /**
     * Class constructor.
     *
     * @access public
     * @param Application $app Silex application object
     */
    public function __construct(Application $app)
    {
        $this->_app = $app;
        $this->_db = $app['db'];
    }

    /**
     *
     * Get information about user
     *
     * @param $id user id
     *
     * @access public
     * @return array Associative array with information about user
     */
    public function getUser($id)
    {
        if (($id != '') && ctype_digit((string) $id)) {
            $sql = 'SELECT * FROM lic_users WHERE iduser = ? LIMIT 1;';
            return $this->_db->fetchAssoc($sql, array((int) $id));
        } else {
            return array();
        }
    }

    /**
     * Dodaje nowego użytkownika
     *
     * @param  Array $data Associative array contains all necessary information
     *
     * @access public
     * @return Void
     */
    public function addUser($data)
    {
        $sprawdz = $this->getUserByLogin($data['login']);

        if (!$sprawdz) {

            $users = "INSERT INTO `lic_users` (`login`, `password`, `email`) VALUES (?,?,?);";
            $this->_db->executeQuery(
                $users, array(
                    $data['login'], $data['password'], $data['email'],
                )
            );

            $sql = "SELECT * FROM lic_users WHERE login = ?";
            $user = $this->_db->fetchAssoc($sql, array($data['login']));

            $addRole = 'INSERT INTO lic_users_roles (iduser, idrole) VALUES(?, ?)';
            $this->_db->executeQuery(
                $addRole, array($user['iduser'], '1')
            );
        }
    }

    /**
     * Zmienia informacje na temat użytkownika
     *
     * @param Array $data Associative array contains all necessary information
     *
     * @access public
     * @return Void
     */
    public function editUser($data)
    {

        if (isset($data['id']) && ctype_digit((string) $data['id'])) {
            $sql = 'UPDATE php_users SET login = ?, password = ?, email = ?
                    WHERE iduser = ?;';
            $this->_db->executeQuery(
                $sql, array($data['login'], $data['password'],$data['email'], $data['id'])
            );
        }
    }

    /**
     * Usuń użytkownika
     *
     * @param Array $data id użytkownika
     *
     * @access public
     * @return void
     */
    public function usunUser($data)
    {
        $sqlj = 'DELETE FROM lic_users_roles WHERE iduser = ?';
        $this->_db->executeQuery($sqlj, array($data['iduser']));
        $sql = 'DELETE FROM `lic_users` WHERE `iduser`= ?';
        $this->_db->executeQuery($sql, array($data['iduser']));
    }


    public function getAllUsers()
    {
        $sql = 'SELECT lic_users.iduser, login, email, name
                FROM `lic_users`
                JOIN lic_users_roles
                ON lic_users.iduser=lic_users_roles.iduser
                JOIN lic_roles ON roles.idrole=users_roles.idrole';
        return $this->_db->fetchAll($sql);
    }

    /**
     * Load user by login.
     *
     * @param String $login
     *
     * @access public
     * @return array
     */
    public function loadUserByLogin($login)
    {
        $data = $this->getUserByLogin($login);

        if (!$data) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        }

        $roles = $this->getUserRoles($data['iduser']);

        if (!$roles) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        }

        $user = array(
            'login' => $data['login'],
            'password' => $data['password'],
            'roles' => $roles
        );

        return $user;
    }

    /**
     * Gets user by id.
     *
     * @param Integer $id
     *
     * @access public
     * @return Array Information about searching user.
     */
    public function getUserById($id)
    {
        $sql = 'SELECT * FROM lic_users WHERE `iduser` = ? Limit 1';
        return $this->_db->fetchAssoc($sql, array((int) $id));
    }

    /**
     * Get user by login.
     *
     * @param String $login
     *
     * @access public
     * @return Array Information about searching user.
     */
    public function getUserByLogin($login)
    {
        $sql = 'SELECT * FROM lic_users WHERE login = ?';
        return $this->_db->fetchAssoc($sql, array((string) $login));
    }

    /**
     * Get users role.
     *
     * @param String $userId
     *
     * @access public
     * @return Array
     */
    public function getUserRoles($userId)
    {
        $sql = '
            SELECT
                lic_roles.role
            FROM
                lic_users_roles
            INNER JOIN
                lic_roles
            ON lic_users_roles.idrole=lic_roles.idrole
            WHERE
                lic_users_roles.iduser = ?
            ';

        $result = $this->_db->fetchAll($sql, array((string) $userId));

        $roles = array();
        foreach ($result as $row) {
            $roles[] = $row['role'];
        }

        return $roles;
    }

    /**
     * Nadawanie roli
     *
     * @param  Integer $iduser
     *
     * @access public
     * @return void
     */
    public function addRole($iduser)
    {
        $sql = "INSERT INTO `lic_users_roles` (`iduser`, `idrole`) VALUES (?,?);";

        $this->_db->executeQuery($sql, array($iduser, '2'));
    }

    /**
     * Get current logged user id
     *
     * @param $app
     *
     * @access public
     * @return mixed
     */
    public function getIdCurrentUser($app)
    {

        $login = $this->getCurrentUser($app);
        $iduser = $this->getUserByLogin($login);

        return $iduser['iduser'];
    }

    /**
     * Informacje o aktualnie zalogowanym użytkowniku
     *
     * @param $app
     *
     * @access protected
     * @return mixed
     */
    protected function getCurrentUser($app)
    {
        $token = $app['security']->getToken();

        if (null !== $token) {
            $user = $token->getUser()->getUsername();
        }

        return $user;
    }

    /**
     * Sprawdzanie, czy istnieje użytkownik o takim id
     *
     * @param Integer $iduser id użytkownika
     *
     * @access public
     * @return bool True if exists
     */
    public function sprawdzUser($iduser)
    {
        $sql = 'SELECT * FROM lic_users WHERE iduser=?';
        $result = $this->_db->fetchAll($sql, array($iduser));

        if ($result) {
            return true;
        } else {
            return false;
        }
    }



    /**
     * Check if user is logged
     *
     * @param Application $app
     *
     * @access public
     * @return bool
     */
    public function _isLoggedIn(Application $app)
    {
        if ('anon.' !== $user = $app['security']->getToken()->getUser()) {
            return true;
        } else {
            return false;
        }
    }

}