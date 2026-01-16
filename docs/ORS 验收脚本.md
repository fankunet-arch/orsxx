# ORS 验收脚本

## 0) 截图/录屏命名规范（强制）

**目标**：证据可追溯、可对照用例、可批量归档。

### 0.1 目录

* 证据统一放：`docs/evidence/`
* 每次验收新建子目录：`docs/evidence/yyyymmdd_run01/`

### 0.2 截图命名（必须用 snake_case）

格式：

* `ors_<tc_id>_s<step_no>_<page_or_action>_<passfail>.png`

示例：

* `ors_tc_d1_s1_ors_home_pass.png`
* `ors_tc_c2_s2_quick_purchase_pass.png`
* `ors_tc_db1_s1_show_tables_pass.png`

### 0.3 录屏命名（仅用于“10秒”计时类）

格式：

* `ors_<tc_id>_<scenario>_<passfail>.mp4`

示例：

* `ors_tc_c1_quick_task_10s_pass.mp4`

---

## 1) 预置条件（执行前必须完成）

### 1.1 环境

* 已部署到目标服务器（Nginx/Apache + PHP + DB）
* 配置文件存在：`app/ors/config_ors/env_ors.php` 
* 两个入口文件存在：

  * Capture：`dc_html/ors/index.php`（对应 `http://<domain>/ors/`）
  * 控制室：`dc_html/ors/ap/index.php`（对应 `http://<domain>/ors/ap`） 

### 1.2 测试账号（必须）

* `admin / <pwd>`
* `staff / <pwd>`

### 1.3 测试项目（必须）

* 项目名：`qa_madrid_50m2`
* target_open_date：任意未来日期（例如 90 天后）

---

# 2) 部署与路由验收（必须 100% Pass）

## TC_D1：入口 URL 可访问且职责正确

**步骤**

1. 打开：`http://<domain>/ors/`
2. 打开：`http://<domain>/ors/ap`

**输入**

* 无

**期望输出**

* `/ors/`：进入手机 Capture 界面或跳登录（页面明显偏移动端）
* `/ors/ap`：进入控制室界面或跳登录（页面明显偏桌面端）
* 两者的导航与功能入口明显不同（避免误用）

**截图**

* `ors_tc_d1_s1_ors_home_pass.png`
* `ors_tc_d1_s2_ors_ap_home_pass.png`

---

## TC_D2：核心目录不可被 URL 直接访问（安全隔离）

（核心分层要求：`app/ors/` 不可直接通过 URL 访问）

**步骤**

1. 访问：`http://<domain>/app/ors/bootstrap.php`
2. 访问：`http://<domain>/app/ors/config_ors/env_ors.php`

**输入**

* 无

**期望输出**

* 返回 403 或 404（不可直接访问）
* 不得返回源码内容

**截图**

* `ors_tc_d2_s1_bootstrap_blocked_pass.png`
* `ors_tc_d2_s2_env_blocked_pass.png`

---

## TC_D3：控制室入口为中央路由 + 统一登录拦截

（控制室入口必须按路由机制：`?action=...`，且路由前统一登录拦截）

**步骤**

1. 未登录状态访问：`http://<domain>/ors/ap/?action=dashboard`
2. 未登录状态访问：`http://<domain>/ors/ap/?action=tasks`

**输入**

* action 参数（如上）

**期望输出**

* 均被拦截到登录页或返回未授权提示（统一拦截）

**截图**

* `ors_tc_d3_s1_ap_action_blocked_pass.png`
* `ors_tc_d3_s2_ap_action_blocked_pass.png`

---

# 3) 数据库命名规则验收（新增：表名必须 ors_ 前缀）

## TC_DB1：所有业务表名必须以 `ors_` 开头（强制）

**步骤**

1. 在 DB 客户端执行：`SHOW TABLES;`
2. 执行：`SHOW TABLES LIKE 'ors\_%';`

**输入**

* SQL 如上

**期望输出**

* 业务表应全部出现在 `ors_%` 集合内
* 不允许出现未带 `ors_` 前缀的业务表（例如 `task`、`purchase` 这种必须判 Fail）
* 允许存在 DB 自带系统表或其他项目表（但 ORS 本项目表必须全部 `ors_`）

**截图**

* `ors_tc_db1_s1_show_tables_pass.png`
* `ors_tc_db1_s2_show_ors_tables_pass.png`

> 建议 ORS 业务表最小集合（供开发对照）：
> `ors_project, ors_task, ors_item, ors_purchase, ors_vendor, ors_lesson`（以及必要的关联表如 `ors_task_dependency`/`ors_template_tag` 等）

---

# 4) 登录与权限验收（必须）

## TC_A1：未登录拦截（两入口一致）

**步骤**

1. 清空浏览器 Cookie/无痕窗口
2. 打开 `/ors/`
3. 打开 `/ors/ap`

**输入**

* 无

**期望输出**

* 两者都必须要求登录或提示未授权
* 不允许直接进入业务页面

**截图**

* `ors_tc_a1_s2_ors_login_required_pass.png`
* `ors_tc_a1_s3_ap_login_required_pass.png`

---

## TC_A2：staff 权限边界（推荐：staff 禁止进入控制室）

**步骤**

1. 用 `staff` 登录
2. 访问：`/ors/`（应可用）
3. 访问：`/ors/ap`（应拒绝或只读极少页面）

**输入**

* staff 账号

**期望输出**

* staff 在 `/ors/` 可正常新增快速记录
* staff 访问 `/ors/ap`：

  * 推荐通过标准：直接拒绝（403/跳转提示无权限）
  * 若允许进入：不得看到“模板管理/报表/供应商管理”等管理能力

**截图**

* `ors_tc_a2_s2_staff_ors_ok_pass.png`
* `ors_tc_a2_s3_staff_ap_denied_pass.png`

---

# 5) 现场 Capture（手机端）验收（必须，且要计时）

## TC_C1：10 秒快速任务（只填标题也能保存）

**步骤**

1. 用 `staff` 或 `admin` 登录 `/ors/`
2. 点击 `+ 快速任务`
3. 只填标题：`买m6膨胀螺丝`
4. 点击保存
5. 进入“今日记录”确认出现

**输入**

* title：`买m6膨胀螺丝`

**期望输出**

* 保存成功（有 toast/提示）
* 今日记录立刻可见
* 从点“快速任务”到保存成功 **≤ 10 秒**

**截图/录屏**

* 录屏：`ors_tc_c1_quick_task_10s_pass.mp4`
* 截图：`ors_tc_c1_s5_today_list_pass.png`

---

## TC_C2：10 秒快速采购（EUR + CNY 双币种）

**步骤**

1. `/ors/` 点击 `+ 快速采购`
2. 填：

   * 物品：`小票打印机`
   * 价格：`120`
   * 币种：`EUR`
   * 数量：`1`（如有）
3. 保存
4. 再新增一条：

   * 物品：`工服`
   * 价格：`300`
   * 币种：`CNY`
   * 数量：`1`
5. 保存
6. 打开“今日采购”确认两条存在

**输入**

* 采购#1：小票打印机 120 EUR
* 采购#2：工服 300 CNY

**期望输出**

* 两条都能保存成功
* CNY 记录必须能看到汇率字段（可用默认快照或后补）
* 采购列表能显示折算 EUR 总额（至少在控制室可见）

**截图/录屏**

* 录屏：`ors_tc_c2_quick_purchase_10s_pass.mp4`
* 截图：

  * `ors_tc_c2_s3_purchase_eur_saved_pass.png`
  * `ors_tc_c2_s5_purchase_cny_saved_pass.png`
  * `ors_tc_c2_s6_today_purchase_list_pass.png`

---

## TC_C3：Capture 端必须“轻”（不得出现重型 PM UI）

**步骤**

1. `/ors/` 浏览所有可点击入口

**输入**

* 无

**期望输出**

* 首页主入口不超过 4 个（建议：快速任务/快速采购/今日记录/搜索）
* 不出现甘特编辑/复杂表格/多级菜单
* 页面样式现代化（非裸 HTML），且有明确按钮与反馈

**截图**

* `ors_tc_c3_s1_ors_home_ui_pass.png`

---

# 6) 控制室 Organize（电脑端）验收（必须）

## TC_O1：任务看板（todo/doing/blocked/done）+ blocked 必填原因

**步骤**

1. admin 登录 `/ors/ap`
2. 进入任务看板
3. 新建或找到一个任务，将其状态改为 `blocked`
4. 尝试不填写阻塞原因直接保存
5. 再填写阻塞原因（例如 `waiting_vendor`）保存

**输入**

* block_reason：`waiting_vendor`

**期望输出**

* 看板存在 4 列：todo/doing/blocked/done
* 第 4 步必须被阻止（提示必须选择阻塞原因）
* 第 5 步保存成功

**截图**

* `ors_tc_o1_s2_kanban_columns_pass.png`
* `ors_tc_o1_s4_block_reason_required_pass.png`
* `ors_tc_o1_s5_block_saved_pass.png`

---

## TC_O2：批量归档（最关键的效率点）

**步骤**

1. 在任务列表勾选至少 5 条任务
2. 批量设置：

   * phase：`procurement`
   * template_flag：true
   * template_tags：`must_buy,it`
3. 保存批量操作
4. 刷新页面确认仍生效

**输入**

* phase_code：`procurement`
* template_flag：true
* template_tags：`must_buy,it`

**期望输出**

* 批量操作成功，且刷新不丢失
* 不因缺字段（如无 vendor/无日期）报错

**截图**

* `ors_tc_o2_s2_bulk_select_pass.png`
* `ors_tc_o2_s3_bulk_update_pass.png`
* `ors_tc_o2_s4_bulk_persist_pass.png`

---

## TC_O3：把 free_text_item 归一化为 Item（物品库沉淀）

**步骤**

1. 打开采购列表，找到 `free_text_item=小票打印机`
2. 点击“归一化/沉淀为物品库”
3. 补充：

   * category：`it_devices`
   * unit：`pcs`
   * must_buy_level：`must`
4. 保存
5. 新建采购时尝试直接选择该 Item

**输入**

* item_name：小票打印机
* category：it_devices
* unit：pcs
* must_buy_level：must

**期望输出**

* 物品库新增 Item
* 原采购记录与 Item 建立关联
* 新建采购可直接选择该 Item（复用成功）

**截图**

* `ors_tc_o3_s2_purchase_found_pass.png`
* `ors_tc_o3_s4_item_created_pass.png`
* `ors_tc_o3_s5_item_selectable_pass.png`

---

# 7) 模板引擎（系统灵魂）验收（必须）

## TC_B1：默认模板内置（你这次遗忘项必须存在）

**步骤**

1. admin 登录 `/ors/ap`
2. 打开模板库（Item/Task/Lesson）
3. 搜索并逐个确认存在以下条目：

   * Items：收银机、钱箱、店用手机、香薰机、广告灯箱、小票打印机、音箱、平板支架、KDS平板、印章、遮挡帘、工作衣帽、路由器
   * Tasks：灭火器合同/证书、电力升级/增容、印章制作
   * Lessons：收银链路漏项、电力未前置、消防合同遗漏

**输入**

* 关键词搜索

**期望输出**

* 三类模板都能找到（否则 Fail）

**截图**

* `ors_tc_b1_s3_items_list_pass.png`
* `ors_tc_b1_s3_tasks_list_pass.png`
* `ors_tc_b1_s3_lessons_list_pass.png`

---

## TC_B2：一键生成新店计划（任务+采购+检查清单）

**步骤**

1. admin 在 `/ors/ap` 新建项目：

   * project_name：`qa_madrid_50m2_v2`
   * project_type：`cafeteria`
   * city：`madrid`
   * area_m2：`50`
   * target_open_date：未来日期
2. 点击 “从模板生成”
3. 打开生成结果：

   * 任务清单
   * 采购清单
   * 检查清单（由 Lesson 生成）

**输入**

* 如上

**期望输出**

* 三张清单都生成成功
* 检查清单必须包含两条关键必查：

  * “签约后3天内电力负载评估…”
  * “开业前7天POS全链路联调实测出票…”
* 对 long_lead / critical_path 项生成 `latest_start_date` 或 `latest_order_date`（至少可见）

**截图**

* `ors_tc_b2_s2_project_created_pass.png`
* `ors_tc_b2_s3_generated_tasks_pass.png`
* `ors_tc_b2_s3_generated_purchases_pass.png`
* `ors_tc_b2_s3_generated_checks_pass.png`

---

# 8) 双币种与汇总验收（必须）

## TC_F1：EUR 汇总口径正确（含手填汇率）

**步骤**

1. 在同一项目下新增 3 条采购：

   * A：100 EUR * 1
   * B：200 CNY * 1，fx_rate_to_eur=0.13
   * C：50 EUR * 2
2. 打开项目成本汇总/采购汇总

**输入**

* A：100 EUR qty=1
* B：200 CNY qty=1 fx=0.13
* C：50 EUR qty=2

**期望输出**

* 折算 EUR 总额 = 100 + (200*0.13) + (50*2) = 100 + 26 + 100 = **226.00 EUR**
* 显示保留 2 位小数

**截图**

* `ors_tc_f1_s1_purchase_inputs_pass.png`
* `ors_tc_f1_s2_cost_summary_226_pass.png`

---

# 9) 稳定性与“先记后补”验收（必须）

## TC_S1：缺字段不崩溃

**步骤**

1. 新建任务只填 title 保存
2. 新建采购只填物品+价格+币种保存
3. 将任务状态改为 doing/done（不填日期）
4. 刷新页面

**输入**

* title 任意
* purchase 任意

**期望输出**

* 全流程无 500、无 PHP 报错页
* 记录可保存、可显示、可后补字段

**截图**

* `ors_tc_s1_s1_min_task_saved_pass.png`
* `ors_tc_s1_s2_min_purchase_saved_pass.png`
* `ors_tc_s1_s4_refresh_ok_pass.png`

---

# 10) UI 现代化验收（必须，有明确判定标准）

## TC_UI1：手机端现代化与可用性

**步骤**

1. 用手机或浏览器移动端模式打开 `/ors/`
2. 检查首页与快速录入页面

**输入**

* 无

**期望输出（全部满足才 Pass）**

* 有明显主按钮（拇指可达），输入控件大小适配
* 保存成功/失败有 toast/提示条
* 非裸 HTML（有卡片/间距/按钮样式）

**截图**

* `ors_tc_ui1_s1_mobile_home_pass.png`
* `ors_tc_ui1_s2_mobile_quick_form_pass.png`

## TC_UI2：控制室现代化与信息密度合理

**步骤**

1. 桌面打开 `/ors/ap`
2. 打开看板/列表/模板库任意 2 个页面

**输入**

* 无

**期望输出**

* 有清晰导航（顶部或侧边任选一种）
* 列表/卡片有状态标签与留白
* 所有保存操作有反馈

**截图**

* `ors_tc_ui2_s1_ap_nav_pass.png`
* `ors_tc_ui2_s2_ap_list_pass.png`

---

## 11) 验收输出格式（AI 必须提交）

AI 开发在 `docs/acceptance_checklist.md` 最后追加一段“验收结果汇总”：

* `pass_count: <n>`
* `fail_count: <n>`
* fail 项逐条列出：`tc_id + 失败原因 + 复现步骤 + 计划修复方式`
* 证据目录：`docs/evidence/yyyymmdd_run01/`


# ORS 验收脚本补充：Fail 典型示例（v1.1-add1）

> 判定原则：**只要命中任一条 Fail 示例 → 该 TC 直接判 Fail**（无需讨论“差不多”）。

---

## TC_D1：入口 URL 可访问且职责正确 — Fail 示例

* **Fail-1**：`/ors/` 与 `/ors/ap` 打开的是同一个页面（同导航、同功能、同布局），无法区分 Capture vs 控制室。

  * 判定点：两者截图对比，菜单项基本一致即 Fail。
* **Fail-2**：`/ors/` 打开后直接进入控制室（出现模板库/报表/批量归档等）。

  * 判定点：手机端出现“模板管理/报表/供应商管理/批量操作”等即 Fail。
* **Fail-3**：任一入口出现 500 / 空白页 / PHP 报错堆栈。

  * 判定点：截图含错误堆栈或浏览器控制台明显报错（影响使用）即 Fail。

---

## TC_D2：核心目录不可 URL 访问 — Fail 示例

* **Fail-1**：访问 `/app/ors/...` 返回 **200** 且显示源码、下载源码、或显示“Warning/Notice”信息。

  * 判定点：任何形式泄露路径/代码/配置内容 → Fail。
* **Fail-2**：返回 302 跳转到可读页面（变相可访问）。

  * 判定点：最终能看到内容即 Fail。

---

## TC_D3：控制室中央路由 + 统一登录拦截 — Fail 示例

* **Fail-1**：未登录访问 `/?action=tasks` 仍可看到任务列表或任何业务数据。
* **Fail-2**：未登录访问某些 action 被拦截，另一些 action 不拦截（拦截不一致）。

  * 判定点：同一入口下 action 行为不一致 → Fail。
* **Fail-3**：拦截方式是“半截页面 + 报错”，而不是明确登录页/未授权提示。

  * 判定点：出现混乱半页面即 Fail。

---

## TC_DB1：所有业务表名必须 `ors_` 前缀 — Fail 示例（强制）

* **Fail-1**：存在任意 ORS 业务表未以 `ors_` 开头（例如 `task`、`purchase`、`vendor`、`project`、`lesson`）。

  * 判定点：`SHOW TABLES;` 里出现此类表 → Fail。
* **Fail-2**：混用前缀（部分 `ors_`，部分 `dms_` 或无前缀）。

  * 判定点：只要出现混用 → Fail。
* **Fail-3**：AI 说“表名可改/未来再改”，但当前交付脚本未达成。

  * 判定点：验收按现状，未达成即 Fail。

---

## TC_A1：未登录拦截（两入口一致）— Fail 示例

* **Fail-1**：未登录进入了任何业务列表（任务/采购/供应商/模板）。
* **Fail-2**：未登录时出现“能新增记录但看不到列表”的奇怪状态。

  * 判定点：未登录必须统一不可用（或统一跳登录），否则 Fail。
* **Fail-3**：登录页缺少明确错误提示（比如密码错无反馈），导致无法完成登录流程。

  * 判定点：无法稳定登录即 Fail。

---

## TC_A2：staff 权限边界 — Fail 示例（推荐 staff 禁入控制室）

* **Fail-1**：staff 能进入 `/ors/ap` 并看到模板管理/报表/供应商管理/批量归档。
* **Fail-2**：staff 能修改模板或删除记录。
* **Fail-3**：staff 在 `/ors/` 无法正常快速录入（Capture 反而不好用）。

  * 判定点：staff 角色的核心价值是“现场录入”，录入不顺畅即 Fail。

---

## TC_C1：10 秒快速任务 — Fail 示例

* **Fail-1**：必须填很多字段才能保存（例如必须选供应商/日期/负责人/费用）。

  * 判定点：只填 title 不能保存 → Fail。
* **Fail-2**：保存后无明确反馈（不知道是否成功）。

  * 判定点：没有 toast/提示条/成功状态 → Fail。
* **Fail-3**：新增任务后在“今日记录”找不到（实际没写入）。
* **Fail-4（计时）**：从点“快速任务”到保存成功 > 10 秒（在正常网络下）。

  * 判定点：录屏计时超过 10 秒 → Fail。

---

## TC_C2：10 秒快速采购（双币种）— Fail 示例

* **Fail-1**：CNY 记录无法保存，或保存后币种丢失。
* **Fail-2**：没有汇率字段入口（既没有默认快照，也不能后补），导致无法折算 EUR。
* **Fail-3**：total_price_eur 永远是空/0，且控制室也无法汇总。
* **Fail-4（计时）**：两条采购任一条录入 > 10 秒。
* **Fail-5**：录入页面像“复杂采购系统”（大量字段、下拉、多步骤）。

  * 判定点：现场录入必须极简，复杂即 Fail。

---

## TC_C3：Capture 端必须轻 — Fail 示例（硬卡）

* **Fail-1**：首页入口超过 4 个，或者出现多级菜单。
* **Fail-2**：出现甘特图、复杂筛选器、大表格、批量操作。
* **Fail-3**：Capture 端出现“模板库/报表/项目复制”等控制室功能。
* **Fail-4**：页面完全裸 HTML（无卡片/按钮样式/间距）。

  * 判定点：看起来像“开发测试页”即 Fail。

---

## TC_O1：看板 + blocked 必填原因 — Fail 示例

* **Fail-1**：看板缺列（不是 todo/doing/blocked/done 四列齐全）。
* **Fail-2**：blocked 状态允许空 `block_reason` 直接保存。

  * 判定点：必须强制，未强制即 Fail。
* **Fail-3**：保存后状态不落库（刷新回退）。
* **Fail-4**：blocked 原因只能写自由文本、没有枚举（会导致复盘不可统计）。

  * 判定点：必须能选择固定枚举之一，否则 Fail。

---

## TC_O2：批量归档 — Fail 示例

* **Fail-1**：没有批量功能，或只能一条条改。
* **Fail-2**：批量改完刷新丢失（未持久化）。
* **Fail-3**：批量操作导致部分记录报错/失败（但不给出失败明细）。

  * 判定点：必须反馈“成功多少/失败多少+原因”，否则 Fail。
* **Fail-4**：因为字段缺失（没 vendor/没日期）就不允许批量更新。

  * 判定点：必须支持“先记后补”，阻止即 Fail。

---

## TC_O3：free_text_item 归一化为 Item — Fail 示例

* **Fail-1**：没有“归一化/沉淀物品库”的动作入口。
* **Fail-2**：沉淀后，采购记录仍旧无法关联 Item（只能纯文本）。
* **Fail-3**：沉淀后，新建采购仍无法选择该 Item（复用失败）。
* **Fail-4**：沉淀过程强制填大量字段（导致晚上整理很痛苦）。

  * 判定点：允许只补关键字段（category/unit/must）即可。

---

## TC_B1：默认模板内置 — Fail 示例

* **Fail-1**：模板库为空，或缺少你指定的遗忘项任意一条。
* **Fail-2**：AI 声称“可以导入”，但系统交付时没有内置。

  * 判定点：验收看交付系统现状，没内置就是 Fail。
* **Fail-3**：Lesson 模板没有 `prevention_check_item`。

  * 判定点：该字段必须存在且非空，否则 Fail。

---

## TC_B2：一键生成新店计划 — Fail 示例（最关键）

* **Fail-1**：只生成任务，不生成采购/检查清单（三者缺任一项即 Fail）。
* **Fail-2**：检查清单没有两条硬性必查：

  * “签约后3天内电力负载评估…”
  * “开业前7天POS全链路联调实测出票…”
* **Fail-3**：生成后没有 `latest_start_date` 或 `latest_order_date`（至少对 long_lead/critical_path 项）。
* **Fail-4**：生成规则依赖大量人工选择/填写（变成 PM 软件）。

  * 判定点：只允许选择 project_type/city/area/open_date 后一键生成，否则 Fail。

---

## TC_F1：双币种汇总 = 226.00 EUR — Fail 示例（硬算式）

* **Fail-1**：汇总值不是 **226.00**（容许误差：0.01 内仅因四舍五入；超过即 Fail）。
* **Fail-2**：CNY 汇率输入无法覆盖默认值（或输入后不生效）。
* **Fail-3**：汇总页找不到（等于无法验证）。

  * 判定点：必须有“项目成本汇总”或“采购汇总”可视化入口。

---

## TC_S1：缺字段不崩 — Fail 示例

* **Fail-1**：任何一步出现 500 / PHP 报错页。
* **Fail-2**：保存时直接提示“缺少必填字段”且无法跳过（违背先记后补）。
* **Fail-3**：刷新后记录消失或变乱码。
* **Fail-4**：输入异常字符导致崩溃（例如 `"`、`'`、`/`、emoji）。

  * 判定点：至少不崩溃，且有合理提示或自动转义。

---

## TC_UI1：手机端现代化 — Fail 示例（可见即 Fail）

* **Fail-1**：页面像默认裸 HTML（纯文本+原生按钮+无间距）。
* **Fail-2**：按钮太小/输入框太小，手机不好点。
* **Fail-3**：保存无反馈（用户不确定是否成功）。
* **Fail-4**：首屏信息密度过高，出现大量字段/表格。

---

## TC_UI2：控制室现代化 — Fail 示例

* **Fail-1**：没有清晰导航（找不到“任务/采购/模板/供应商”入口）。
* **Fail-2**：列表没有状态标签/留白，阅读困难。
* **Fail-3**：批量操作入口隐藏很深或根本没有。
* **Fail-4**：保存/删除没有确认与反馈（容易误操作且不可追溯）。

---

# 追加：AI 提交的“证据最小集合”（防偷工）

AI 最终提交必须至少包含以下证据文件（否则视为未完成验收）：

* `docs/evidence/yyyymmdd_run01/` 目录存在
* 至少 **20 张截图** + **2 个录屏**（TC_C1、TC_C2）
* `docs/acceptance_checklist.md` 末尾汇总：pass/fail 数量 + fail 明细 + 证据目录路径

