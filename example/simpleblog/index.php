define('APPPATH',dirname(__FILE__));
require '../../Iota/Iota.php';
 
require 'model.php';
require 'config.php';
 
$urls = array(
    '/' => 'Index',
    '/view/(\d+)' => 'View',
    '/create' => 'Create',
    '/delete/(\d+)' => 'Delete',
    '/edit/(\d+)' => 'Edit',
    ); 
 
Iota::renderBase('base');
 
class Index {
    function GET(){
        $posts = model::findAll();
        Iota::render('index',array('posts'=>$posts),'page');
    }
}
class View {
    function GET($id){
        $post = model::findById($id);
        Iota::render('view',array('post'=>$post),'page');
    }
}
class Create {
    function GET(){
        Iota::render('create',null,'page');
    }
    function POST(){
        $post = new model();
        $post->title = Iota::post('title');
        $post->content = Iota::post('content');
        $post->save();
        Iota::redirect('/');
    }
}
class Delete {
    function POST($id){
        $blog = new model($id);
        $blog->delete();
        Iota::redirect('/');
    }
}
class Edit {
    function GET($id){
        $post = model::findById($id);
        Iota::render('edit', array('post'=>$post),'page');
    }
    function POST($id){
        $post = model::findById($id);
        $post->title = Iota::post('title'); 
        $post->content = Iota::post('content'); 
        $post->save();
        Iota::redirect('/');
    }
}
$app = Iota::application($urls);
$app->run();
