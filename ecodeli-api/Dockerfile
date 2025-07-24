FROM node:18-alpine

WORKDIR /usr/src/app

COPY package*.json ./
RUN npm install --omit=dev && npm rebuild

COPY . .

EXPOSE 8001

# Attendre MariaDB avant de lancer l'API (sans script externe)
CMD sh -c "echo '⌛ Attente MariaDB...' && \
           until nc -z db 3306; do sleep 1; done && \
           echo '✅ MariaDB OK. Lancement API.' && \
           node index.js"
