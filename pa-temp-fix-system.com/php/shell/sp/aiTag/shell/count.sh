#!/bin/bash
# AI打标数量定时统计（每1小时，由 crontab 调用）
# crontab 示例（路径按实际部署替换；分钟位可改，如整点用 0）：
#   20 * * * * /bin/bash /path/to/php/shell/sp/aiTag/shell/count.sh >> /tmp/aiTagCount.log 2>&1

# cron 环境 PATH 精简且无 shell alias，必须用完整路径指定 php（含 -d pcre.jit=0，与交互终端 alias 保持一致）
# 部署到服务器时改为服务器 php 路径（如 /xp/server/php/php-7.4/bin/php）
PHP_BIN="/Users/clouddre/tools/company/ux168/runtimes/php/php-7.4.33-runtime/bin/php"
cd "$(dirname "$0")/.."
"$PHP_BIN" -d pcre.jit=0 SpAiTagCountController.php
