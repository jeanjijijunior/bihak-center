# Website Not Accessible - Troubleshooting Guide

## Current Issue
Testers report that `http://155.248.239.239` and `http://155.248.239.239/index.php` are not accessible.

Error: **Connection Refused** - This means the server is not accepting HTTP connections.

---

## Quick Fix Commands

Run these commands on the server to diagnose and fix the issue:

### 1. Check Apache Status
```bash
sudo systemctl status apache2
```

**If Apache is not running:**
```bash
sudo systemctl start apache2
sudo systemctl enable apache2
```

### 2. Check Firewall Rules
```bash
sudo ufw status
```

**If firewall is blocking port 80:**
```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw reload
```

### 3. Check if Apache is Listening on Port 80
```bash
sudo netstat -tlnp | grep :80
```

**Should show something like:**
```
tcp6  0  0 :::80  :::*  LISTEN  1234/apache2
```

### 4. Check Apache Error Logs
```bash
sudo tail -50 /var/log/apache2/error.log
```

### 5. Test Locally on Server
```bash
curl http://localhost
```

**If this works but external access doesn't, it's a firewall issue.**

---

## Oracle Cloud Specific: Security List Rules

Oracle Cloud requires you to open ports in the **Security List** (not just UFW):

### Steps to Open HTTP Port in Oracle Cloud:

1. **Login to Oracle Cloud Console**
   - Go to: https://cloud.oracle.com

2. **Navigate to your Instance**
   - Menu → Compute → Instances
   - Click on your instance: `bihak-web-server-v2`

3. **Open Security List**
   - Click on the subnet link (under "Primary VNIC")
   - Click on the Security List name
   - Click "Add Ingress Rules"

4. **Add HTTP Rule**
   ```
   Source CIDR: 0.0.0.0/0
   IP Protocol: TCP
   Source Port Range: All
   Destination Port Range: 80
   Description: HTTP access
   ```

5. **Add HTTPS Rule** (for future SSL)
   ```
   Source CIDR: 0.0.0.0/0
   IP Protocol: TCP
   Source Port Range: All
   Destination Port Range: 443
   Description: HTTPS access
   ```

6. **Click "Add Ingress Rules"**

---

## Complete Server Setup Commands

Run all these commands in sequence on your server:

```bash
# 1. Check and start Apache
sudo systemctl status apache2
sudo systemctl start apache2
sudo systemctl enable apache2

# 2. Configure firewall
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp
sudo ufw --force enable
sudo ufw reload

# 3. Verify Apache is listening
sudo netstat -tlnp | grep :80

# 4. Test local access
curl -I http://localhost

# 5. Check DocumentRoot
grep DocumentRoot /etc/apache2/sites-available/000-default.conf

# 6. Verify file permissions
ls -la /var/www/html/public/

# 7. Test PHP processing
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/public/test.php
curl http://localhost/test.php
```

---

## Correct URLs for Testers

Once the server is accessible, provide these URLs:

### Main Website
```
http://155.248.239.239
```
*(Note: No `/index.php` needed - Apache will serve it automatically)*

### Specific Pages
```
Homepage:        http://155.248.239.239/
About:           http://155.248.239.239/about.php
Programs:        http://155.248.239.239/work.php
Stories:         http://155.248.239.239/stories.php
Signup:          http://155.248.239.239/signup.php
Login:           http://155.248.239.239/login.php
Contact:         http://155.248.239.239/contact.php
```

---

## Why `/index.php` Appears in URL

The website is configured with `DirectoryIndex index.php`, which means:
- `http://155.248.239.239/` automatically serves `index.php`
- You don't need to type `/index.php` explicitly
- Both URLs work the same way

---

## Verification Checklist

Run this command to get a full system check:
```bash
cd /var/www/html
bash verify-deployment.sh
```

---

## Common Issues and Solutions

### Issue 1: Connection Refused
**Cause:** Apache not running or firewall blocking
**Solution:** Start Apache and configure firewall (see above)

### Issue 2: 403 Forbidden
**Cause:** Wrong file permissions
**Solution:**
```bash
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
```

### Issue 3: 404 Not Found
**Cause:** Wrong DocumentRoot
**Solution:**
```bash
# DocumentRoot should be /var/www/html/public
sudo nano /etc/apache2/sites-available/000-default.conf
# Change DocumentRoot to: /var/www/html/public
sudo systemctl reload apache2
```

### Issue 4: Blank Page
**Cause:** PHP error or database connection issue
**Solution:**
```bash
sudo tail -f /var/log/apache2/error.log
```

---

## Contact Information

If issues persist after following this guide:
1. Check Apache error logs: `sudo tail -f /var/log/apache2/error.log`
2. Check PHP error logs: `sudo tail -f /var/log/php*.log`
3. Verify Oracle Cloud Security List rules (most common issue)

---

## Quick Status Check

Run this one-liner to check everything:
```bash
echo "=== Apache ===" && sudo systemctl is-active apache2 && \
echo "=== Port 80 ===" && sudo netstat -tlnp | grep :80 && \
echo "=== Firewall ===" && sudo ufw status | grep 80 && \
echo "=== Local Test ===" && curl -I http://localhost 2>&1 | head -5
```

Expected output:
```
=== Apache ===
active
=== Port 80 ===
tcp6  0  0 :::80  :::*  LISTEN  1234/apache2
=== Firewall ===
80/tcp  ALLOW  Anywhere
=== Local Test ===
HTTP/1.1 200 OK
```
