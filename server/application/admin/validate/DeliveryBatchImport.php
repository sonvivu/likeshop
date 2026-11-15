<?php

namespace app\admin\validate;

class DeliveryBatchImport extends \think\Validate
{
    public function __construct(array $rules = [], array $message = [], array $field = [])
    {
        parent::__construct($rules, $message, $field);
        foreach ($this->message as $key => $value) {
            $this->message[$key] = lang($value);
        }
        foreach ($this->rule as $key => $rule) {
            if (strpos($key, '|') !== false) {
                list($attr, $title) = explode('|', $key, 2);
                $newKey = $attr . '|' . lang($title);
                if ($newKey !== $key) {
                    $this->rule[$newKey] = $rule;
                    unset($this->rule[$key]);
                }
            }
        }
    }

    protected $rule = [
        'lists'     => [ 'require', 'checkLists' ],
    ];
    
    protected $message = [
        'lists.require' => '上传文件不能为空',
    ];
    
    function checkLists($lists, $rule, $data)
    {
        $nums = count($lists) - 1;
        if ($nums < 1) {
            return lang('导入的文件发货信息为空');
        }
    
        if ($nums > 2000) {
            return lang('单次最多导入2000条发货信息');
        }
    
        foreach ($lists as $key => $info) {
            if (empty(trim($info['A'])) || empty(trim($info['B'])) || empty(trim($info['C']))) {
                return lang('发货信息：第') . ($key + 2) . lang('行存在未填写的发货信息');
            }
            
            if (mb_strlen($info['A']) > 255) {
                return lang('发货信息：第') . ($key + 2) . lang('行发货信息 订单号 超过长度255');
            }
        
            if (mb_strlen($info['B']) > 255) {
                return lang('发货信息：第') . ($key + 2) . lang('行发货信息超过长度255');
            }
        
            if (mb_strlen($info['C']) > 255) {
                return lang('发货信息：第') . ($key + 2) . lang('行发货信息超过长度255');
            }
        }
        
        return true;
    }
}