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

use App\Model\Enums\Tenant\Status;
use App\Schema\TenantSchema;
use Hyperf\Validation\Request\FormRequest;
use Mine\Swagger\Attributes\FormRequest as FormRequestAnnotation;

#[FormRequestAnnotation(
    schema: TenantSchema::class,
    title: '租户管理请求',
    required: [
        'name',
        'contact_name'
    ],
    only: [
        'name',
        'code',
        'parent_agent_id',
        'contact_name',
        'contact_phone',
        'contact_email',
        'status',
        'ai_config',
        'quota_limit',
        'expire_at',
        'remark',
        'admin_user'
    ]
)]
class TenantRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50|unique:tenant,code',
            'parent_agent_id' => 'nullable|integer|exists:agent,id',
            'contact_name' => 'required|string|max:50',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:100',
            'status' => 'nullable|string|in:' . implode(',', array_column(Status::cases(), 'value')),
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
            'admin_user.quota_limit' => 'nullable|numeric|min:0',
        ];

        // 更新时，排除当前记录的唯一性验证
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $id = $this->route('id');
            $rules['code'] = 'nullable|string|max:50|unique:tenant,code,' . $id;
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => trans('tenant.tenant.name'),
            'code' => trans('tenant.tenant.code'),
            'parent_agent_id' => trans('tenant.tenant.parent_agent_id'),
            'contact_name' => trans('tenant.tenant.contact_name'),
            'contact_phone' => trans('tenant.tenant.contact_phone'),
            'contact_email' => trans('tenant.tenant.contact_email'),
            'status' => trans('tenant.tenant.status'),
            'ai_config' => trans('tenant.tenant.ai_config'),
            'quota_limit' => trans('tenant.tenant.quota_limit'),
            'expire_at' => trans('tenant.tenant.expire_at'),
            'remark' => trans('tenant.tenant.remark'),
            
            'admin_user' => trans('tenant.tenant.admin_user'),
            'admin_user.username' => trans('tenant.tenant.admin_user.username'),
            'admin_user.password' => trans('tenant.tenant.admin_user.password'),
            'admin_user.nickname' => trans('tenant.tenant.admin_user.nickname'),
            'admin_user.phone' => trans('tenant.tenant.admin_user.phone'),
            'admin_user.email' => trans('tenant.tenant.admin_user.email'),
            'admin_user.quota_limit' => trans('tenant.tenant.admin_user.quota_limit'),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
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
                $exists = \App\Model\Tenant\TenantUser::where('username', $username)->exists();
                if ($exists) {
                    $validator->errors()->add('admin_user.username', '管理员用户名已存在');
                }
            }

            // 验证配额限制合理性
            if ($this->has('quota_limit') && $this->has('admin_user.quota_limit')) {
                $tenantQuota = (float) $this->input('quota_limit', 0);
                $adminQuota = (float) $this->input('admin_user.quota_limit', 0);
                
                if ($adminQuota > $tenantQuota) {
                    $validator->errors()->add('admin_user.quota_limit', '管理员配额不能超过租户配额');
                }
            }
        });
    }
}