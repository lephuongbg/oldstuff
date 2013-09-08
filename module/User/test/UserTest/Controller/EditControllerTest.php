<?php
namespace UserTest\Controller;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use User\Entity\User;

class EditControllerTest extends AbstractHttpControllerTestCase
{
    protected $traceError = true;
    protected $em = null;
    protected $user = null;
    public function setUp()
    {
        $this->setApplicationConfig(\UserTest\Bootstrap::getConfig());
        parent::setUp();
        
        // Load the entity manager once
        if ($this->em == null) {
            $this->em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');
            $tool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
            $classes = array($this->em->getClassMetadata('User\Entity\User'));
            $tool->dropSchema($classes);
            $tool->createSchema($classes);
        }
        
        // Create a new user once
        if ($this->user == null) {
            $this->user = new User();
            $this->user->populate(array(
                'user_id' => 1,
                'display_name' => 'User',
                'email' => 'user@example.com',
                'password' => 'test',
                'state' => 1,
            ));
            $this->em->persist($this->user);
            $this->em->flush();
        }
    }
    
    public function testEditPage()
    {
        $this->dispatch('/user/edit/'.$this->user->user_id);
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('User');
        $this->assertControllerName('User\Controller\User');
        $this->assertControllerClass('UserController');
        $this->assertMatchedRouteName('user');
        
        // Make sure an edit form exists
        $this->assertQuery('form');
    }
    
    public function testChangeDisplayName()
    {
        $this->user->display_name = 'Another User';
        $this->dispatch('/user/edit/'.$this->user->user_id, 'POST', $this->user->getArrayCopy());
        
//         $this->assertQuery('input[name="display_name"][value="'.$this->user->display_name.'"]');
    }
    
    public function testUnspecifiedIdPage()
    {
        $this->dispatch('/user/edit');
        $this->assertResponseStatusCode(404);
    }
}