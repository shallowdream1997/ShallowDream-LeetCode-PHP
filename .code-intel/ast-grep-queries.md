# AST 定向检索

```bash
# Java：Feign、Controller、Rabbit 和方法调用
ast-grep --lang java -p '@FeignClient($$$)' <repo>
ast-grep --lang java -p '@RabbitListener($$$)' <repo>
ast-grep --lang java -p '$OBJECT.$METHOD($$$)' <repo>

# PHP：路由、方法
ast-grep --lang php -p 'function $NAME($$$) { $$$ }' <repo>

# TypeScript：导出与请求调用
ast-grep --lang typescript -p 'export $KIND $NAME = $$$' <repo>
```
