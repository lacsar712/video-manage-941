# 第一阶段：构建前端
FROM node:18 AS builder

WORKDIR /app

# 复制 package.json 和 package-lock.json
COPY package*.json ./

# 安装依赖
RUN npm install

# 复制源代码
COPY . .

# 构建前端
RUN npm run build

# 第二阶段：使用 Nginx 提供静态文件
FROM nginx:stable

# 从构建阶段复制构建产物到 Nginx 目录
COPY --from=builder /app/dist /usr/share/nginx/html/admin

# 复制 Nginx 配置（如果需要）
# COPY nginx.conf /etc/nginx/conf.d/default.conf

# 暴露端口
EXPOSE 80

# 启动 Nginx
CMD ["nginx", "-g", "daemon off;"]
