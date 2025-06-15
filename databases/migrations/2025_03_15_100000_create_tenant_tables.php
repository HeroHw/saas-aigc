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

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateTenantTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 租户表
        Schema::create('tenant', static function (Blueprint $table) {
            $table->comment('租户表');
            $table->bigIncrements('id')->comment('租户ID,主键');
            $table->string('name', 100)->comment('租户名称');
            $table->string('code', 50)->unique()->comment('租户编码');
            $table->bigInteger('parent_agent_id')->default(0)->comment('所属代理ID');
            $table->string('contact_name', 50)->default('')->comment('联系人姓名');
            $table->string('contact_phone', 20)->default('')->comment('联系人电话');
            $table->string('contact_email', 100)->default('')->comment('联系人邮箱');
            $table->tinyInteger('status')->default(1)->comment('状态:1=正常,2=停用,3=待审核');
            $table->json('ai_config')->nullable()->comment('AI配置信息');
            $table->decimal('quota_limit', 15, 2)->default(0)->comment('配额限制');
            $table->decimal('quota_used', 15, 2)->default(0)->comment('已使用配额');
            $table->timestamp('expire_at')->nullable()->comment('过期时间');
            $table->authorBy();
            $table->datetimes();
            $table->softDeletes();
            $table->string('remark', 255)->default('')->comment('备注');
            
            $table->index(['parent_agent_id']);
            $table->index(['status']);
        });

        // 租户用户表
        Schema::create('tenant_user', static function (Blueprint $table) {
            $table->comment('租户用户表');
            $table->bigIncrements('id')->comment('用户ID,主键');
            $table->bigInteger('tenant_id')->comment('所属租户ID');
            $table->string('username', 50)->comment('用户名');
            $table->string('password', 100)->comment('密码');
            $table->string('nickname', 50)->default('')->comment('用户昵称');
            $table->string('phone', 20)->default('')->comment('手机');
            $table->string('email', 100)->default('')->comment('用户邮箱');
            $table->string('avatar', 255)->default('')->comment('用户头像');
            $table->tinyInteger('user_type')->default(1)->comment('用户类型:1=普通用户,2=管理员');
            $table->tinyInteger('status')->default(1)->comment('状态:1=正常,2=停用');
            $table->decimal('quota_limit', 15, 2)->default(0)->comment('个人配额限制');
            $table->decimal('quota_used', 15, 2)->default(0)->comment('已使用配额');
            $table->ipAddress('login_ip')->default('127.0.0.1')->comment('最后登陆IP');
            $table->timestamp('login_time')->useCurrent()->comment('最后登陆时间');
            $table->json('user_setting')->nullable()->comment('用户设置数据');
            $table->authorBy();
            $table->datetimes();
            $table->softDeletes();
            $table->string('remark', 255)->default('')->comment('备注');
            
            $table->unique(['tenant_id', 'username']);
            $table->index(['tenant_id']);
            $table->index(['status']);
        });

        // 配额使用记录表
        Schema::create('quota_usage_log', static function (Blueprint $table) {
            $table->comment('配额使用记录表');
            $table->bigIncrements('id')->comment('记录ID,主键');
            $table->bigInteger('tenant_id')->comment('租户ID');
            $table->bigInteger('tenant_user_id')->nullable()->comment('租户用户ID');
            $table->string('request_id', 100)->default('')->comment('请求ID');
            $table->string('model_name', 100)->comment('模型名称');
            $table->integer('prompt_tokens')->default(0)->comment('输入token数');
            $table->integer('completion_tokens')->default(0)->comment('输出token数');
            $table->integer('total_tokens')->default(0)->comment('总token数');
            $table->decimal('cost', 15, 6)->default(0)->comment('消耗费用');
            $table->string('request_type', 50)->default('chat')->comment('请求类型');
            $table->json('request_data')->nullable()->comment('请求数据');
            $table->json('response_data')->nullable()->comment('响应数据');
            $table->tinyInteger('status')->default(1)->comment('状态:1=成功,2=失败');
            $table->string('error_message', 500)->default('')->comment('错误信息');
            $table->timestamp('request_time')->useCurrent()->comment('请求时间');
            $table->datetimes();
            
            $table->index(['tenant_id', 'request_time']);
            $table->index(['tenant_user_id', 'request_time']);
            $table->index(['model_name']);
            $table->index(['status']);
        });

        // 应用配置表
        Schema::create('tenant_app_config', static function (Blueprint $table) {
            $table->comment('租户应用配置表');
            $table->bigIncrements('id')->comment('配置ID,主键');
            $table->bigInteger('tenant_id')->comment('租户ID');
            $table->string('app_type', 50)->comment('应用类型:chat,image,audio等');
            $table->string('app_name', 100)->comment('应用名称');
            $table->json('app_config')->nullable()->comment('应用配置');
            $table->json('model_config')->nullable()->comment('模型配置');
            $table->tinyInteger('status')->default(1)->comment('状态:1=启用,2=禁用');
            $table->authorBy();
            $table->datetimes();
            $table->softDeletes();
            $table->string('remark', 255)->default('')->comment('备注');
            
            $table->index(['tenant_id']);
            $table->index(['app_type']);
            $table->index(['status']);
        });

        // 代理表
        Schema::create('agent', static function (Blueprint $table) {
            $table->comment('代理表');
            $table->bigIncrements('id')->comment('代理ID,主键');
            $table->string('name', 100)->comment('代理名称');
            $table->string('code', 50)->unique()->comment('代理编码');
            $table->bigInteger('parent_id')->default(0)->comment('父级代理ID,0表示顶级代理');
            $table->string('level_path', 500)->default('')->comment('层级路径,如:1,2,3');
            $table->tinyInteger('level')->default(1)->comment('代理层级');
            $table->string('contact_name', 50)->default('')->comment('联系人姓名');
            $table->string('contact_phone', 20)->default('')->comment('联系人电话');
            $table->string('contact_email', 100)->default('')->comment('联系人邮箱');
            $table->tinyInteger('status')->default(1)->comment('状态:1=正常,2=停用,3=待审核');
            $table->decimal('quota_limit', 15, 2)->default(0)->comment('配额限制');
            $table->decimal('quota_used', 15, 2)->default(0)->comment('已使用配额');
            $table->decimal('quota_allocated', 15, 2)->default(0)->comment('已分配配额');
            $table->json('ai_config')->nullable()->comment('AI配置信息');
            $table->timestamp('expire_at')->nullable()->comment('过期时间');
            $table->authorBy();
            $table->datetimes();
            $table->softDeletes();
            $table->string('remark', 255)->default('')->comment('备注');
            
            $table->index(['parent_id']);
            $table->index(['level']);
            $table->index(['status']);
        });

        // 代理用户表
        Schema::create('agent_user', static function (Blueprint $table) {
            $table->comment('代理用户表');
            $table->bigIncrements('id')->comment('用户ID,主键');
            $table->bigInteger('agent_id')->comment('所属代理ID');
            $table->string('username', 50)->comment('用户名');
            $table->string('password', 100)->comment('密码');
            $table->string('nickname', 50)->default('')->comment('用户昵称');
            $table->string('phone', 20)->default('')->comment('手机');
            $table->string('email', 100)->default('')->comment('用户邮箱');
            $table->string('avatar', 255)->default('')->comment('用户头像');
            $table->tinyInteger('user_type')->default(1)->comment('用户类型:1=普通用户,2=管理员');
            $table->tinyInteger('status')->default(1)->comment('状态:1=正常,2=停用');
            $table->ipAddress('login_ip')->default('127.0.0.1')->comment('最后登陆IP');
            $table->timestamp('login_time')->useCurrent()->comment('最后登陆时间');
            $table->json('user_setting')->nullable()->comment('用户设置数据');
            $table->authorBy();
            $table->datetimes();
            $table->softDeletes();
            $table->string('remark', 255)->default('')->comment('备注');
            
            $table->unique(['agent_id', 'username']);
            $table->index(['agent_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_user');
        Schema::dropIfExists('agent');
        Schema::dropIfExists('tenant_app_config');
        Schema::dropIfExists('quota_usage_log');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('tenant');
    }
}