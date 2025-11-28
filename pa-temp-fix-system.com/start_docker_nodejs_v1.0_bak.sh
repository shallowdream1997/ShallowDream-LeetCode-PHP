#!/bin/bash
source /etc/profile

productName="product_operation"
basePath="/export/js/product_operation/"
nodejsLibs="/export/nodejs_libs/nestjs_lib/"
startNodeCommand=""
startNodeCommandWithWatch=""
basepath=$(cd `dirname $0`; pwd)

# stop docker
echo -e "关闭docker"
$basepath/onekey_stop.sh ${productName}
# exit

#web
web_name="product_operation_js_angular5"
web_path=${basePath}${web_name}
web_env_dev_port="8001"

#common library
lib="ux168_composite_lib"
libPath="/export/js/"${lib}

if [ -z "$1" ]; then
    echo "不开启nodejs项目端口";
else
    echo "开启nodejs项目端口: $1";
    # 使用逗号分隔符将字符串分割成数组
    IFS=',' read -ra portsArray <<< "$1"

    # 遍历数组并打印每个元素
    for hosts in "${portsArray[@]}"; do
        case "$hosts" in
        3009)
        nodeAppPortMap[3009]="product_operation_nodejs_app";;
        3015)
        nodeAppPortMap[3015]="product_operation_listing_management_nodejs_app";;
        3022)
        nodeAppPortMap[3022]="product_operation_distribution_nodejs_app";;
        3023)
        nodeAppPortMap[3023]="product_operation_sold_nodejs_app";;
        3016)
        nodeAppPortMap[3016]="product_operation_qms_nodejs_app";;
        3010)
        nodeAppPortMap[3010]="cets_nodejs_app";;
        3013)
        nodeAppPortMap[3013]="ux168_nodejs_app";;
        3033)
        nodeAppPortMap[3033]="product_operation_channel";;
        3028)
        nodeAppPortMap[3028]="product_operation_pl_nodejs_app";;
        3031)
        nodeAppPortMap[3031]="walmart_pl_new_nodejs_app";;
        3035)
        nodeAppPortMap[3035]="product_operation_log_nodejs_app";;
        3044)
        nestjsPortMap[3044]="poms_listing_nestjs";;
        *)
        echo "未知nodejs项目端口: ${hosts}"
        echo "用法: $0 {3022,3023,3016,3010,3013,3033,3028,3031,3035,3044,3045}"
        exit 1
        ;;
        esac
    done
fi

echo "开启network: $2"; #--network="host"

netWork=""
if [ "$2" == "network" ]; then
  netWork="--network="host""
fi

dockerCommand="docker run -it -d
   --dns 172.16.10.181
   -p 12333:12333
   -p 9229:9229
   -p ${web_env_dev_port}:${web_env_dev_port} ${netWork}"

portMap=""
pathMap=""
i=0
for key in ${!nodeAppPortMap[@]}
do
    num=$i
    if [[ $i == 0 ]]; then
        num=""
    fi
    portMap="$portMap -p ${key}:${key}"
    pathMap="$pathMap -v ${basePath}${nodeAppPortMap[$key]}/config:/home/mean${num}/config"
    pathMap="$pathMap -v ${basePath}${nodeAppPortMap[$key]}/modules:/home/mean${num}/modules"
    pathMap="$pathMap -v ${basePath}${nodeAppPortMap[$key]}/logs:/home/mean${num}/logs"
    startNodeCommand="${startNodeCommand}cd /home/mean${num}; pm2 start server.js --name=\"${nodeAppPortMap[$key]}\"\n"
    startNodeCommandWithWatch="${startNodeCommandWithWatch}#${nodeAppPortMap[$key]}: \ncd /home/mean${num}; pm2 start  config/start_dev.yml \n"
    let "i+=1"
done

for key in ${!nestjsPortMap[@]}
do
    num=$i
    if [[ $i == 0 ]]; then
        num=""
    fi
    portMap="$portMap -p ${key}:${key}"
    pathMap="$pathMap -v ${basePath}${nestjsPortMap[$key]}/env:/home/mean${num}/env"
    pathMap="$pathMap -v ${basePath}${nestjsPortMap[$key]}/src:/home/mean${num}/src"
    pathMap="$pathMap -v ${basePath}${nestjsPortMap[$key]}/logs:/home/mean${num}/logs"
    pathMap="$pathMap -v ${basePath}${nestjsPortMap[$key]}/libs:/home/mean${num}/libs"
    startNodeCommand="${startNodeCommand}cd /home/mean${num}; pm2 start server.js --name=\"${nodeAppPortMap[$key]}\"\n"
    startNodeCommandWithWatch="${startNodeCommandWithWatch}#${nodeAppPortMap[$key]}: \ncd /home/mean${num}; pm2 start  config/start_dev.yml \n"
    let "i+=1"
done

dockerCommand="${dockerCommand}
  ${portMap}
  ${pathMap}
  -p 16889:16889
  -p 16890:16890
  -p 16891:16891
  -p 16892:16892
  -p 9009:9009
  -p 9015:9015
  -p 9023:9023
  -p 9028:9028
  -p 9033:9033
  -p 9035:9035
  -p 9044:9044
  -v ${libPath}:/home/${lib}
  -v ${nodejsLibs}:/home/nodejs_libs
  -v /tmp:/tmp
  -v /etc/localtime:/etc/localtime
  -v ${web_path}:/home/angular-starter
  --name ${productName}
  dockerimages-v2.ux168.cn:5000/nodejs_app_dev_v1.0:poms /bin/bash"

#dockerCommand="${dockerCommand}
#  /bin/sh -c \"cd /home/angular-starter && ln -s /opt/angular-starter/node_modules node_modules && /startup.sh\""

echo -e "进入docker"
echo -e "docker-nsenter product_operation\n"

echo -e "启动angular"
echo -e "cd /home/angular-starter/; ln -s /opt/angular-starter/node_modules node_modules; npm run server:dev:hmr\n"

echo -e "启动nestjs: "
echo -e "cd /home/angular-starter/scripts/ ; ./init_nestjs.sh  ; cd /home/mean9/ ; npm run start "

echo -e "停止并重新启动node.js（按需启动，非开发模式）"
echo -e "pm2 stop all"
echo -e "pm2 delete all"
echo -e ${startNodeCommand}

echo -e "停止并重新启动node.js（按需启动，开发模式，启动较慢）"
echo -e "pm2 stop all"
echo -e "pm2 delete all"
echo -e ${startNodeCommandWithWatch}

# echo -e ${dockerCommand}
$dockerCommand

imageInfo=$(docker images --digests dockerimages-v2.ux168.cn:5000/nodejs_app_dev_v1.0:poms)
latestImageId="47511d243d0b"
if [[ $imageInfo =~ $latestImageId ]]
then
    echo ""
else
    printf "\033[31;1m
\033[5m🌟\033[25m docker镜像有更新，请按以下步骤更新镜像:
\033[5m🌟\033[25m （1）进入scripts文件夹，执行 ./docker_helper
\033[5m🌟\033[25m （2）输入poms更新镜像
\033[5m🌟\033[25m （3）执行 ./start_docker_nodejs_v1.0.sh 重新生成容器
\033[0m"
fi

exit
