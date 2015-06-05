<?php
/** Authentication Controller
 *
 * PHP version 5
 *
 * @category Controller
 * @package Controller
 * @author Agnieszka Gorgolewska
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link wierzba.wzks.uj.edu.pl/~12_gorgolewska
 **/
namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Model\UserModel;

/**
 * Class AuthController
 *
 * @category Controller
 * @package  Controller
 * @author   Agnieszka Gorgolewska <agnieszka.gorgolewska@uj.edu.pl>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     wierzba.wzks.uj.edu.pl/~12_gorgolewska
 * @uses Silex\Application
 * @uses Silex\ControllerProviderInterface
 * @uses Symfony\Component\HttpFoundation\Request
 * @uses Symfony\Component\Validator\Constraints
 * @uses Model\UserModel
 */
class AuthController implements ControllerProviderInterface
{
    /**
     * Connection
     *
     * @param Application $app application object
     *
     * @return \Silex\ControllerCollection
     */

    public function connect(Application $app)
    {
        $this->_model = new UserModel($app);
        $authController = $app['controllers_factory'];
        $authController->match('/login', array($this, 'login'))
            ->bind('/auth/login');
        $authController->match('/logout', array($this, 'logout'))
            ->bind('/auth/logout');
        return $authController;
    }

    /**
     * login
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page.
     */
    public function login(Application $app, Request $request)
    {
        $data = array();

        $form = $app['form.factory']->createBuilder('form')
            ->add(
                'login', 'text', array(
                    'label' => 'Login', 'data' => $app['session']->get(
                        '_security.last_username'
                    )
                )
            )
            ->add(
                'haslo', 'password', array(
                    'label' => 'Hasło')
            )
            ->getForm();

        return $app['twig']->render(
            'auth/login.twig', array(
                'form' => $form->createView(),
                'error' => $app['security.last_error']($request)
            )
        );
    }

    /**
     * logout
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return mixed Generates page.
     */
    public function logout(Application $app, Request $request)
    {
        $app['session']->clear();
        return $app['twig']->render('auth/logout.twig');
    }

}