<?php

namespace Ledc\Webman\Patch;

/**
 * 修复"topthink/think-validate": ">=2.0", 在高版本中报错
 */
trait FixValidate
{
    /**
     * 使用filter_var方式验证
     * @access public
     * @param  mixed $value  字段值
     * @param  mixed $rule  验证规则
     * @return bool
     */
    public function filter(mixed $value, mixed $rule): bool
    {
        if (is_string($rule) && strpos($rule, ',')) {
            list($rule, $param) = explode(',', $rule);
        } elseif (is_array($rule)) {
            $param = $rule[1] ?? null;
            $rule  = $rule[0];
        } else {
            $param = null;
        }

        if (is_null($param)) {
            // fix：在高版本中报错 david 2025年1月21日 14:19:08
            return false !== filter_var($value, is_int($rule) ? $rule : filter_id($rule));
        }
        return false !== filter_var($value, is_int($rule) ? $rule : filter_id($rule), $param);
    }
}