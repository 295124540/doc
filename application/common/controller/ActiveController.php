<?php
namespace app\common\controller;

class ActiveController extends ApiBaseController
{

    protected $modelClass;
    protected $condition;
    protected $order;

    /**
     * 初始方法
     */
    public function _initialize(){
        //api跨域
        header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Headers:Origin,X-Requested-With,Content-Type,Accept");
        header("Access-Control-Allow-Methods:DELETE,PUT");

        if(!$this->modelClass){
            error('控制器的modelClass未定义！');
        }
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    /**
     * find model
     * @param $id
     * @return mixed
     */
    protected  function findModel($id){

        $modelClass = $this->modelClass;
        $this->condition['id']=$id;
        $model = $modelClass::get($this->condition);

        if(!$model){
            error("你没权限操作或该记录已经被删除！");
        }
        return $model;
    }

    /**
     * find list
     * @return mixed
     */
    protected function prepareDataProvider(){
        $modelClass = $this->modelClass;
        if($this->condition){
            $query = $modelClass::where($this->condition);
        }

        if($this->order){
            if(isset($query)){
                $query = $query->order($this->order);
            }else{
                $query = $modelClass::order($this->order);
            }
        }

        if(isset($query)){
            $list = $query->paginate(20);
        }else{
            $list = $modelClass::paginate(20);
        }
        return $list;
    }


    /**
     * 列表（分页）
     */
    public function index(){
        $list = $this->prepareDataProvider();
        success($list);
    }

    /**
     * 详情
     */
    public function read($id){
        $model = $this->findModel($id);
        if($model){
            success($model);
        }else{
            error("没有改ID的结果记录哦！");
        }

    }

    /**
     * 保存（新增）
     */
    public function save(){

        $param = $_POST;
        if($param == null)$param = json_decode(file_get_contents("php://input"),true);
        if($param == null)error("你没有提交参数哦！");

        $modelClass = $this->modelClass;
        $model = new $modelClass($param);
        $model->allowField(true)->save();

        if($model){
            success($model);
        }else{
            error($model->getError());
        }
    }

    /**
     * 修改
     */
    public function update($id){

        $param = request()->put();
        if($param == null)error("你没有提交参数哦！");

        $modelClass = $this->modelClass;
        $this->condition['id']=$id;
        $m = new $modelClass();
        $rt = $m ->allowField(true)->save($param, $this->condition);
        if($rt){
            success($m);
        }else{
            error("修改失败，你有可能没有修改任何参数！".$m->getError());
        }
    }

    /**
     * 删除
     */
    public function delete($id){

        $modelClass = $this->modelClass;
        $this->condition['id']=$id;
        $rt = $modelClass::destroy($this->condition);
        if($rt){
            success();
        }else{
            error($modelClass->getError);
        }
    }


}
