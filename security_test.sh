#!/bin/bash

BASE_URL="http://127.0.0.1:8000/api"
EMAIL="security_test_$(date +%s)@example.com"
PASSWORD="password123"
NAME="Security Tester"

echo "---------------------------------------------------"
echo "Security Audit & Hardening Test Script"
echo "Target: $BASE_URL"
echo "---------------------------------------------------"

# 1. Registration Test (Get Default Role)
echo "\n[TEST 1] Registering new user..."
REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "'"$NAME"'",
    "email": "'"$EMAIL"'",
    "password": "'"$PASSWORD"'",
    "password_confirmation": "'"$PASSWORD"'"
  }')

TOKEN=$(echo $REGISTER_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
USER_ID=$(echo $REGISTER_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
ROLE=$(echo $REGISTER_RESPONSE | grep -o '"role":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
  echo "❌ Registration failed or token not found."
  echo "Response: $REGISTER_RESPONSE"
  exit 1
else
  echo "✅ Registration successful."
  echo "User ID: $USER_ID"
  echo "Assigned Role: $ROLE"
  echo "Token: ${TOKEN:0:10}..."
fi

# 2. Privilege Escalation Test (Accessing Admin Route)
echo "\n[TEST 2] Testing Admin Access with '$ROLE' role..."
ADMIN_RESPONSE_BODY=$(curl -s -X GET "$BASE_URL/admin/users" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "Response Body: $ADMIN_RESPONSE_BODY"

if echo "$ADMIN_RESPONSE_BODY" | grep -q "Unauthorized" || echo "$ADMIN_RESPONSE_BODY" | grep -q "Forbidden" || echo "$ADMIN_RESPONSE_BODY" | grep -q "right permissions"; then
  echo "✅ Access Denied (Status: 403/401). Middleware is blocking properly."
else
  echo "⚠️  CRITICAL VULNERABILITY FOUND: Non-admin user ('$ROLE') could access admin route!"
fi

# 3. SQL Injection Test (Login)
echo "\n[TEST 3] Testing SQL Injection on Login..."
SQL_PAYLOAD="' OR '1'='1"
SQL_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "'"$SQL_PAYLOAD"'",
    "password": "anything"
  }')

if echo "$SQL_RESPONSE" | grep -q "Login successful" || echo "$SQL_RESPONSE" | grep -q "token"; then
  echo "❌ VULNERABILITY FOUND: SQL Injection seems effective!"
else
  echo "✅ SQL Injection failed (Safe). Response contains valid error or validation message."
fi

# 4. XSS Test (Registration Payload)
echo "\n[TEST 4] Testing XSS Payload in Registration..."
XSS_NAME="<script>alert('XSS')</script>"
XSS_EMAIL="xss_test_$(date +%s)@example.com"
XSS_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "'"$XSS_NAME"'",
    "email": "'"$XSS_EMAIL"'",
    "password": "'"$PASSWORD"'",
    "password_confirmation": "'"$PASSWORD"'"
  }')

# Check if payload is reflected in response
if echo "$XSS_RESPONSE" | grep -q "<script>alert('XSS')</script>"; then
    echo "⚠️  POTENTIAL XSS: Payload reflected in response JSON."
    # Note: JSON response reflection is low risk unless rendered by frontend without escaping.
else
    echo "✅ XSS Payload not reflected raw."
fi

echo "\n---------------------------------------------------"
echo "Test Complete."
