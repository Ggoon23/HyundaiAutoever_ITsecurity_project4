# Roundcube Webmail - Docker Setup

## 실행 방법

### 1. Docker 컨테이너 시작
```bash
cd webmail
docker-compose up -d
```

### 2. 메일 계정 생성
컨테이너가 완전히 시작된 후 (약 30초 대기):

```bash
# aa@aa.aa 계정 생성
docker exec roundcube_mail setup email add aa@aa.aa password

# bb@aa.aa 계정 생성
docker exec roundcube_mail setup email add bb@aa.aa password
```

또는 Windows에서:
```powershell
docker exec roundcube_mail setup email add aa@aa.aa password
docker exec roundcube_mail setup email add bb@aa.aa password
```

### 3. 웹메일 접속
- URL: http://localhost:8080
- 계정:
  - `aa@aa.aa` / `password`
  - `bb@aa.aa` / `password`

## 서비스 구성

- **Roundcube**: 포트 8080 (웹메일 인터페이스)
- **Mail Server**: 포트 25, 143, 587, 993
- **MySQL**: 내부 네트워크 (외부 노출 안됨)

## 컨테이너 관리

```bash
# 로그 확인
docker-compose logs -f

# 재시작
docker-compose restart

# 중지
docker-compose down

# 완전 삭제 (데이터 포함)
docker-compose down -v
```

## 문제 해결

메일 서버 로그 확인:
```bash
docker-compose logs mail
```

Roundcube 에러 로그 확인:
```bash
docker-compose exec roundcube cat logs/errors.log
```
