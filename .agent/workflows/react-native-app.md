---
description: Hướng dẫn phát triển React Native App cho hệ thống BĐS
---

# React Native App Development Workflow

## Bước 1: Khởi tạo Project

```bash
# Tạo project với Expo (khuyến nghị)
npx create-expo-app@latest app_bds_mobile --template blank-typescript
cd app_bds_mobile
```

// turbo

## Bước 2: Cài đặt Dependencies

```bash
# Navigation
npm install @react-navigation/native @react-navigation/native-stack @react-navigation/bottom-tabs react-native-screens react-native-safe-area-context

# API & State
npm install axios zustand

# Storage
npm install @react-native-async-storage/async-storage

# UI
npm install react-native-vector-icons react-native-toast-message

# Forms
npm install react-hook-form zod @hookform/resolvers

# Utilities
npm install date-fns
```

## Bước 3: Tạo cấu trúc thư mục

```bash
mkdir -p src/{api,components/{common,property,layout},screens/{auth,home,property,profile},navigation,store,hooks,types,utils,theme}
```

## Bước 4: Tạo API Client

Tạo file `src/api/client.ts`:

```typescript
import axios from "axios";
import AsyncStorage from "@react-native-async-storage/async-storage";

const API_URL = "http://YOUR_SERVER_IP:8000/api/v1";
const API_KEY = "YOUR_API_KEY";

const client = axios.create({
    baseURL: API_URL,
    headers: {
        "Content-Type": "application/json",
        "X-API-Key": API_KEY,
    },
});

client.interceptors.request.use(async (config) => {
    const token = await AsyncStorage.getItem("access_token");
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export default client;
```

## Bước 5: Tạo Auth Store

Tạo file `src/store/authStore.ts` với Zustand để quản lý authentication state.

## Bước 6: Tạo Navigation

Setup React Navigation với:

- AuthStack (Login, ForgotPassword)
- MainTabs (Home, Properties, Profile)
- RootNavigator (switch giữa Auth và Main)

## Bước 7: Tạo màn hình

Tạo các screens theo thứ tự:

1. LoginScreen
2. HomeScreen
3. PropertyListScreen
4. PropertyDetailScreen
5. ProfileScreen

## Bước 8: Test trên thiết bị

```bash
# Chạy Expo
npx expo start

# Scan QR code bằng Expo Go app trên điện thoại
```

---

## API Endpoints Chính

| Endpoint            | Method | Mô tả             |
| ------------------- | ------ | ----------------- |
| `/auth/login`       | POST   | Đăng nhập         |
| `/auth/logout`      | POST   | Đăng xuất         |
| `/me`               | GET    | Thông tin user    |
| `/properties`       | GET    | Danh sách BĐS     |
| `/properties/:id`   | GET    | Chi tiết BĐS      |
| `/properties`       | POST   | Tạo BĐS           |
| `/dicts/areas`      | GET    | Danh sách khu vực |
| `/dicts/categories` | GET    | Danh mục          |

---

## Tips

1. Luôn test API bằng Postman trước
2. Sử dụng TypeScript để catch lỗi sớm
3. Chia nhỏ components
4. Handle loading và error states
5. Cache data với AsyncStorage khi cần
