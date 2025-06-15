<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace App\Http\Admin\Request\Tenant;

use App\Http\Common\Request\Traits\NoAuthorizeTrait;
use App\Model\Enums\Tenant\Status;
use App\Schema\AgentSchema;
use Hyperf\Validation\Request\FormRequest;
use Mine\Swagger\Attributes\FormRequest as FormRequestAnnotation;

#[FormRequestAnnotation(
    schema: AgentSchema::class,
    title: '代理管理请求',
    required: [
        'name',
        'contact_name'
    ],
    only: [
        'name',
        'code',
        'parent_id',
        'contact_name',
        'contact_phone',
        'contact_email',
        'status',
        'commission_rate',
        'ai_config',
        'quota_limit',
        'expire_at',
        'remark',
        'admin_user'
    ]
)]
class AgentRequest extends FormRequest
{
    use NoAuthorizeTrait;

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50|unique:agent,code',
            'parent_id' => 'nullable|integer|exists:agent,id',
            'contact_name' => 'required|string|max:50',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:100',
            'status' => 'nullable|string|in:' . implode(',', array_column(Status::cases(), 'value')),
            'commission_rate' => 'nullable|numeric|min:0|max:1',
            'ai_config' => 'nullable|array',
            'quota_limit' => 'nullable|numeric|min:0',
            'expire_at' => 'nullable|date|after:now',
            'remark' => 'nullable|string|max:500',
            
            // 管理员用户信息（创建时）
            'admin_user' => 'nullable|array',
            'admin_user.username' => 'required_with:admin_user|string|max:50',
            'admin_user.password' => 'nullable|string|min:6|max:32',
            'admin_user.nickname' => 'nullable|string|max:50',
            'admin_user.phone' => 'nullable|string|max:20',
            'admin_user.email' => 'nullable|email|max:100',
        ];

        // 更新时，排除当前记录的唯一性验证
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $id = $this->route('id');
            $rules['code'] = 'nullable|string|max:50|unique:agent,code,' . $id;
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => trans('tenant.agent.name'),
            'code' => trans('tenant.agent.code'),
            'parent_id' => trans('tenant.agent.parent_id'),
            'contact_name' => trans('tenant.agent.contact_name'),
            'contact_phone' => trans('tenant.agent.contact_phone'),
            'contact_email' => trans('tenant.agent.contact_email'),
            'status' => trans('tenant.agent.status'),
            'commission_rate' => trans('tenant.agent.commission_rate'),
            'ai_config' => trans('tenant.agent.ai_config'),
            'quota_limit' => trans('tenant.agent.quota_limit'),
            'expire_at' => trans('tenant.agent.expire_at'),
            'remark' => trans('tenant.agent.remark'),
            
            'admin_user' => trans('tenant.agent.admin_user'),
            'admin_user.username' => trans('tenant.agent.admin_user.username'),
            'admin_user.password' => trans('tenant.agent.admin_user.password'),
            'admin_user.nickname' => trans('tenant.agent.admin_user.nickname'),
            'admin_user.phone' => trans('tenant.agent.admin_user.phone'),
            'admin_user.email' => trans('tenant.agent.admin_user.email'),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // 验证不能设置自己为上级代理
            if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
                $id = $this->route('id');
                $parentId = $this->input('parent_id');
                
                if ($parentId && $parentId == $id) {
                    $validator->errors()->add('parent_id', '不能设置自己为上级代理');
                }
            }

            // 验证AI配置格式
            if ($this->has('ai_config') && !empty($this->input('ai_config'))) {
                $aiConfig = $this->input('ai_config');
                if (!is_array($aiConfig)) {
                    $validator->errors()->add('ai_config', 'AI配置必须是数组格式');
                }
            }

            // 验证管理员用户名唯一性（创建时）
            if ($this->isMethod('POST') && $this->has('admin_user.username')) {
                $username = $this->input('admin_user.username');
                $exists = \App\Model\Tenant\AgentUser::where('username', $username)->exists();
                if ($exists) {
                    $validator->errors()->add('admin_user.username', '管理员用户名已存在');
                }
            }

            // 验证佣金比例格式
            if ($this->has('commission_rate')) {
                $rate = $this->input('commission_rate');
                if ($rate !== null && (!is_numeric($rate) || $rate < 0 || $rate > 1)) {
                    $validator->errors()->add('commission_rate', '佣金比例必须在0-1之间');
                }
            }

            // 验证上级代理层级限制（防止层级过深）
            if ($this->has('parent_id') && $this->input('parent_id')) {
                $parentId = $this->input('parent_id');
                $parent = \App\Model\Tenant\Agent::find($parentId);
                
                if ($parent && $parent->level >= 5) { // 假设最大层级为5
                    $validator->errors()->add('parent_id', '代理层级不能超过5级');
                }
            }
        });
    }
}