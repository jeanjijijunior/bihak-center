# Quick Start Guide - Bihak Center Platform

**Last Updated:** November 30, 2025

---

## 🚀 WHAT'S NEW

Three major features have been implemented:

1. **Analytics Dashboard** - Track all platform metrics
2. **Interactive Incubation** - Problem Tree with AI assistance
3. **AI Assistant** - Real-time feedback and chat

---

## 📊 1. ANALYTICS DASHBOARD

### Access:
```
URL: /public/admin/analytics.php
Or: Admin Panel → System → Analytics
```

### Features:
- View user statistics and trends
- Monitor mentorship relationships
- Track messaging activity
- Analyze incubation progress
- Export reports (PDF, Excel, Print)

### Requirements:
- Admin login required
- No additional setup needed
- Works immediately

---

## 🎨 2. INTERACTIVE INCUBATION - PROBLEM TREE

### Access:
```
URL: /public/incubation-interactive-exercise.php?exercise_id=2
Or: Incubation Dashboard → Problem Statement Exercise
```

### Features:
- Drag-and-drop problem tree builder
- Add problems, causes, and effects
- Connect with arrows
- AI feedback on your work
- Chat with AI assistant
- Export to PDF
- Auto-save progress

### Requirements:
- User must be part of an incubation team
- Profile must be approved
- Claude API key needed for AI features (optional for basic functionality)

---

## 🤖 3. AI ASSISTANT

### Setup Required:

**Step 1: Get Claude API Key**
```
1. Visit: https://www.anthropic.com
2. Sign up for account
3. Go to: https://console.anthropic.com/
4. Create API key
5. Copy the key
```

**Step 2: Configure API Key**
```php
// Create file: config/ai-config.php

<?php
define('ANTHROPIC_API_KEY', 'sk-ant-your-key-here');
define('ANTHROPIC_API_URL', 'https://api.anthropic.com/v1/messages');
define('ANTHROPIC_MODEL', 'claude-3-sonnet-20240229');
?>
```

**Step 3: Update API Files**
```php
// In both files:
// - api/incubation-interactive/ai-feedback.php
// - api/incubation-interactive/ai-chat.php

// Replace the callClaudeAPI() function with:

function callClaudeAPI($prompt) {
    require_once __DIR__ . '/../../config/ai-config.php';

    $data = [
        'model' => ANTHROPIC_MODEL,
        'max_tokens' => 1024,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ];

    $ch = curl_init(ANTHROPIC_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . ANTHROPIC_API_KEY,
        'anthropic-version: 2023-06-01'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        error_log("Claude API Error: HTTP $http_code - $response");
        throw new Exception("AI service temporarily unavailable");
    }

    return json_decode($response, true);
}
```

**Step 4: Test AI Features**
```
1. Go to Problem Tree exercise
2. Add some boxes
3. Click "Get AI Feedback"
4. Verify feedback displays
5. Test chat: Ask "What is a problem tree?"
6. Verify AI responds
```

---

## 📁 FILES STRUCTURE

```
bihak-center/
├── public/
│   ├── admin/
│   │   └── analytics.php ← NEW
│   └── incubation-interactive-exercise.php ← NEW
├── api/
│   └── incubation-interactive/ ← NEW FOLDER
│       ├── save-data.php
│       ├── ai-feedback.php
│       ├── ai-chat.php
│       └── submit-exercise.php
├── assets/
│   ├── css/
│   │   └── incubation-interactive.css ← NEW
│   └── js/
│       └── incubation/ ← NEW FOLDER
│           ├── problem-tree.js
│           └── ai-assistant.js
├── config/
│   └── ai-config.php ← CREATE THIS
└── includes/
    └── incubation_interactive_schema.sql ← RAN
```

---

## 🗄️ DATABASE

### Tables Created:
1. `incubation_interactive_data`
2. `incubation_ai_feedback`
3. `incubation_ai_chat`
4. `incubation_knowledge_base`
5. `incubation_exercise_metrics`

### To Verify:
```sql
SHOW TABLES LIKE 'incubation%';
```

Should show 10 tables (5 new + 5 existing).

---

## 🧪 TESTING CHECKLIST

### Analytics Dashboard:
```
☐ Login as admin
☐ Navigate to Analytics
☐ Verify charts load
☐ Check data accuracy
☐ Test export buttons
```

### Problem Tree Module:
```
☐ Login as user (approved profile)
☐ Join or create incubation team
☐ Access Problem Statement exercise
☐ Click "Problem" button - box appears
☐ Double-click box - can edit text
☐ Drag box - moves smoothly
☐ Add multiple boxes
☐ Click "Arrow" - connect boxes
☐ Click "Delete" - removes selected box
☐ Click "Save Draft" - saves successfully
☐ Reload page - work is restored
☐ Click "Export PDF" - downloads PDF
```

### AI Features (Requires API Key):
```
☐ Configure API key
☐ Build a problem tree (3+ boxes)
☐ Click "Get AI Feedback"
☐ Wait 3-5 seconds
☐ Feedback card appears with score
☐ Strengths and improvements listed
☐ Progress bar updates
☐ Checklist updates
☐ Type question in chat
☐ Click "Send"
☐ AI responds within 3 seconds
☐ Conversation history shows
```

---

## ⚠️ COMMON ISSUES & FIXES

### Issue: Analytics page shows errors
**Fix:**
```sql
-- Check if dashboard_stats view exists
SHOW TABLES LIKE '%dashboard%';

-- If missing, check admin dashboard creation
```

### Issue: Problem Tree canvas not showing
**Fix:**
```
1. Check browser console (F12)
2. Verify Konva.js loaded (check network tab)
3. Clear browser cache
4. Try different browser
```

### Issue: AI feedback returns error
**Fix:**
```
1. Verify API key is correct
2. Check config/ai-config.php exists
3. Test API key at https://console.anthropic.com/
4. Check error logs in browser console
5. Verify curl is enabled in PHP
```

### Issue: "You are not part of any team"
**Fix:**
```sql
-- Check team membership
SELECT * FROM incubation_team_members WHERE user_id = YOUR_USER_ID;

-- If empty, create team or add to existing team
INSERT INTO incubation_team_members (team_id, user_id, role)
VALUES (1, YOUR_USER_ID, 'member');
```

### Issue: Boxes won't move/edit
**Fix:**
```
1. Make sure canvas is fully loaded
2. Check JavaScript console for errors
3. Verify Konva.js version is 9.2.0
4. Try refreshing page
```

---

## 💰 COST ESTIMATES

### Claude API Pricing (as of Nov 2025):

**Claude 3 Sonnet (Recommended):**
- Input: $3 per million tokens
- Output: $15 per million tokens

**Estimated Usage:**
- AI Feedback: ~500 tokens input, ~300 tokens output = $0.006 per feedback
- AI Chat: ~200 tokens input, ~150 tokens output = $0.003 per message

**For 100 teams:**
- 100 AI feedbacks/month = $0.60
- 1000 chat messages/month = $3.00
- **Total: ~$5-10/month**

Very affordable!

---

## 📞 SUPPORT CONTACTS

### Development Issues:
- Check documentation files in project root
- Review code comments
- Check error logs

### Claude API Issues:
- https://support.anthropic.com
- https://docs.anthropic.com/claude/docs

### General Platform:
- Review COMPLETE-IMPLEMENTATION-SUMMARY.md
- Check FEATURES-IMPLEMENTATION-SUMMARY.md

---

## 🎯 NEXT STEPS

### Immediate (Day 1):
1. ✅ Review this guide
2. ⏳ Configure Claude API key
3. ⏳ Test Problem Tree
4. ⏳ Test AI features

### Short-term (Week 1):
1. Populate knowledge base
2. Test with real incubation team
3. Gather feedback
4. Fix any bugs

### Medium-term (Weeks 2-4):
1. Build Business Model Canvas module
2. Build Persona Builder module
3. Build Stakeholder Map module
4. Deploy to production

---

## ✅ SUCCESS CRITERIA

You'll know it's working when:

- ✅ Admin can view analytics dashboard
- ✅ Users can build problem trees
- ✅ AI provides helpful feedback
- ✅ Chat assistant answers questions
- ✅ Work saves and loads correctly
- ✅ PDF export works
- ✅ Submission workflow completes

---

## 🎉 YOU'RE READY!

All core features are implemented and ready to use. Just configure the Claude API key and start testing!

---

**Questions?** Review the comprehensive documentation files for detailed information.

**Good luck with your incubation program! 🚀**
