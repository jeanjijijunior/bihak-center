# Interactive Incubation Module Implementation Plan

**Date:** November 30, 2025
**Priority:** HIGH - Enhanced user experience for incubation program

---

## 📋 OVERVIEW

Transform the incubation module from static file uploads to interactive, guided exercises with built-in tools and AI-powered feedback.

---

## 🎯 OBJECTIVES

### 1. Interactive Exercise Modules
Make each incubation exercise interactive with custom tools based on design thinking methodologies documented in the orientation guides.

### 2. AI Assistant Integration
Provide real-time feedback and guidance using AI that understands:
- Content from FICHES ORIENTATION folder
- Incubation best practices
- Design thinking principles
- Business model canvas framework

### 3. Pre-Submission Validation
Allow teams to get AI feedback before submitting to admin for final approval.

---

## 📚 EXERCISES TO IMPLEMENT

Based on orientation guides and current database:

### Phase 1: Ideation & Problem Definition

1. **Team Formation**
   - Interactive team member roles assignment
   - Skills matrix visualization
   - Team charter creator

2. **Problem Statement** → **Problem Tree (Arbre à problème)**
   - Interactive drag-and-drop problem tree
   - Add causes, effects, root problems
   - Visual tree diagram with connecting lines
   - Export as image/PDF

3. **Target Audience** → **Personas & User Stories**
   - Persona builder with templates
   - User story cards (drag-and-drop)
   - Empathy map creator

4. **Market Research** → **Stakeholder Mapping**
   - Interactive stakeholder map
   - Influence/Interest matrix
   - Relationship visualizer

5. **Initial Solution Concept** → **Brainstorming & Design Challenge**
   - Digital brainstorming board
   - Idea clustering tool
   - Voting/prioritization system

### Phase 2: Solution Development

6. **Value Proposition**
   - Value proposition canvas
   - Interactive fill-in template
   - Benefits vs features mapper

7. **Features & Requirements**
   - Feature prioritization matrix
   - MoSCoW method tool
   - User story mapping

8. **Business Model Canvas**
   - Interactive 9-block BMC
   - Drag-and-drop sticky notes
   - Export/print functionality

9. **Financial Projections**
   - Simple financial calculator
   - Revenue/cost projections
   - Break-even analysis tool

10. **Implementation Timeline**
    - Interactive Gantt chart
    - Milestone tracker
    - Resource allocation visualizer

### Phase 3: Prototyping & Testing

11. **Prototype Development** → **Choose What to Prototype**
    - Prototyping method selector
    - Low/high fidelity options
    - Interactive mockup creator

12. **User Testing Plan**
    - Test scenario builder
    - Interview question generator
    - Observation checklist creator

13. **Conduct User Testing** → **Tests & Feedback**
    - Feedback collection forms
    - Results dashboard
    - Insight categorizer

14. **Iterate & Improve** → **5 Whys Analysis**
    - Interactive 5 Whys tool
    - Problem root cause analyzer
    - Solution brainstorming

15. **Impact Measurement**
    - Theory of change builder
    - Impact metrics selector
    - Progress tracker

### Phase 4: Launch & Scale

16. **Launch Strategy**
    - Launch checklist creator
    - Risk assessment matrix
    - Go-to-market plan template

17. **Marketing & Communication**
    - Marketing canvas
    - Channel strategy planner
    - Content calendar

18. **Sustainability Plan**
    - Sustainability business model
    - Partnership mapper
    - Revenue stream analyzer

19. **Growth Roadmap**
    - Scaling strategy planner
    - Milestone roadmap
    - Resource needs projector

---

## 🛠️ TECHNICAL IMPLEMENTATION

### Frontend Components

**1. Canvas-Based Drawing Tools**
- HTML5 Canvas for visual diagrams
- Konva.js for interactive shapes
- Drag-and-drop using interact.js

**2. Rich Text Editors**
- Quill.js for formatted text input
- Markdown support for documentation

**3. Collaborative Features**
- Real-time collaboration (WebSocket)
- Team member tagging
- Comment threads

**4. Export Functionality**
- html2canvas for screenshots
- jsPDF for PDF generation
- JSON export for data portability

### Backend Structure

```
/api/incubation-interactive/
├── problem-tree.php          # Problem tree CRUD
├── persona.php                # Persona builder
├── business-model-canvas.php  # BMC data
├── stakeholder-map.php        # Stakeholder mapping
├── financial-calculator.php   # Financial projections
└── ai-feedback.php            # AI assistant API
```

### Database Schema Updates

```sql
-- Store interactive exercise data
CREATE TABLE IF NOT EXISTS incubation_interactive_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    exercise_id INT NOT NULL,
    data_type VARCHAR(50) NOT NULL, -- 'problem_tree', 'persona', 'bmc', etc.
    data_json TEXT NOT NULL,        -- JSON structure of the interactive data
    version INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES incubation_teams(id),
    FOREIGN KEY (exercise_id) REFERENCES incubation_exercises(id),
    INDEX idx_team_exercise (team_id, exercise_id)
);

-- Store AI feedback
CREATE TABLE IF NOT EXISTS incubation_ai_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    exercise_id INT NOT NULL,
    interactive_data_id INT,
    feedback_text TEXT NOT NULL,
    feedback_type VARCHAR(50), -- 'suggestion', 'validation', 'improvement'
    ai_model VARCHAR(50),      -- 'claude-3-sonnet', etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES incubation_teams(id),
    FOREIGN KEY (exercise_id) REFERENCES incubation_exercises(id),
    FOREIGN KEY (interactive_data_id) REFERENCES incubation_interactive_data(id),
    INDEX idx_team_exercise (team_id, exercise_id)
);
```

---

## 🤖 AI ASSISTANT IMPLEMENTATION

### Knowledge Base

**1. Document Indexing**
- Parse all FICHES ORIENTATION documents
- Extract key concepts, methodologies, examples
- Create searchable knowledge base

**2. AI Model Integration**
- Use Claude API (Anthropic)
- Context: Orientation guides + user's current work
- Provide specific, actionable feedback

### AI Features

**1. Real-Time Guidance**
```javascript
// User working on problem tree
"Click 'Get AI Suggestion' → AI analyzes current tree"
→ "Your problem tree has 3 root causes. Consider adding effects."
→ "Suggestion: Break down 'lack of access' into specific barriers."
```

**2. Pre-Submission Review**
```javascript
// Before submitting to admin
"Click 'AI Review' → Comprehensive analysis"
→ Completeness score (70%)
→ Missing elements highlighted
→ Suggestions for improvement
→ Quality indicators
```

**3. Contextual Help**
```javascript
// Inline help system
User hovers over "Value Proposition"
→ AI explains concept with example from orientation guides
→ Shows similar successful projects
```

### AI Prompt Structure

```
You are an expert incubation coach helping young entrepreneurs in Rwanda develop their social impact projects. You have access to:

1. Design Thinking Framework (from orientation guides)
2. Business Model Canvas methodology
3. Problem Tree Analysis techniques
4. Persona development best practices

Current Exercise: {exercise_name}
Team's Current Work: {interactive_data_json}

Provide specific, constructive feedback on:
- Completeness
- Depth of analysis
- Alignment with best practices
- Suggestions for improvement
- Examples from successful projects

Be encouraging but honest. Highlight strengths and areas to develop.
```

---

## 📱 USER INTERFACE DESIGN

### Exercise Page Layout

```
┌─────────────────────────────────────────────────────────┐
│  Exercise: Problem Tree Analysis                         │
│  [Progress: 3/5 steps] [AI Assistant ▼]                 │
├─────────────────────────────────────────────────────────┤
│                                                           │
│  Instructions Panel          │   Interactive Canvas      │
│  ┌──────────────────┐       │   ┌─────────────────┐    │
│  │ 1. Define core   │       │   │                  │    │
│  │    problem       │       │   │   [Problem]      │    │
│  │                  │       │   │       ↓          │    │
│  │ 2. Identify     │       │   │   [Cause 1]      │    │
│  │    root causes   │       │   │   [Cause 2]      │    │
│  │                  │       │   │       ↑          │    │
│  │ 3. Map effects   │       │   │   [Effect 1]     │    │
│  │                  │       │   │                  │    │
│  └──────────────────┘       │   └─────────────────┘    │
│                              │                           │
│  Toolbox:                    │   Team Comments:          │
│  [Add Box] [Add Arrow]       │   💬 Sarah: Great!       │
│  [Text] [Delete]             │   💬 John: Add...        │
│                              │                           │
├─────────────────────────────────────────────────────────┤
│  [Save Draft] [Get AI Feedback] [Submit for Review]     │
└─────────────────────────────────────────────────────────┘
```

### AI Assistant Sidebar

```
┌─────────────────────────┐
│  🤖 AI Assistant        │
├─────────────────────────┤
│                         │
│  💡 Suggestions:        │
│  • Your problem tree    │
│    needs more depth     │
│  • Consider adding...   │
│                         │
│  📚 Learn More:         │
│  • Problem Tree Guide   │
│  • Example: Clean...    │
│                         │
│  ❓ Ask a Question:     │
│  [Type your question]   │
│  [Ask AI]               │
│                         │
│  ✅ Checklist:          │
│  ☑ Core problem defined │
│  ☐ 3+ root causes       │
│  ☐ Effects mapped       │
│                         │
└─────────────────────────┘
```

---

## 🔄 IMPLEMENTATION PHASES

### Phase 1: Foundation (Week 1)
- ✅ Database schema updates
- ✅ API endpoints structure
- ✅ Base UI components
- ✅ Canvas library integration

### Phase 2: Priority Exercises (Week 2)
- Problem Tree (Interactive)
- Business Model Canvas
- Persona Builder
- Stakeholder Map

### Phase 3: AI Integration (Week 3)
- Claude API setup
- Knowledge base creation
- Feedback system
- Pre-submission review

### Phase 4: Remaining Exercises (Week 4)
- Financial calculator
- Timeline creator
- All other interactive tools

### Phase 5: Testing & Polish (Week 5)
- User testing with real teams
- Bug fixes
- Performance optimization
- Documentation

---

## 📊 SUCCESS METRICS

1. **Engagement**
   - Time spent on interactive exercises vs file uploads
   - Completion rates per exercise
   - AI assistant usage frequency

2. **Quality**
   - Admin approval rate (target: 80%+)
   - Revision requests decreased
   - Feedback sentiment from teams

3. **Learning**
   - Team confidence scores
   - Knowledge retention
   - Application of concepts

---

## 🎨 EXAMPLE: PROBLEM TREE IMPLEMENTATION

### Frontend (JavaScript)

```javascript
class ProblemTree {
    constructor(canvasId) {
        this.stage = new Konva.Stage({
            container: canvasId,
            width: 1000,
            height: 600
        });

        this.layer = new Konva.Layer();
        this.stage.add(this.layer);

        this.problems = [];
        this.causes = [];
        this.effects = [];
        this.connections = [];
    }

    addProblemBox(text, x, y, type) {
        const box = new Konva.Group({
            x: x,
            y: y,
            draggable: true
        });

        const rect = new Konva.Rect({
            width: 200,
            height: 80,
            fill: type === 'problem' ? '#ef4444' :
                  type === 'cause' ? '#f59e0b' : '#10b981',
            stroke: '#1f2937',
            strokeWidth: 2,
            cornerRadius: 8
        });

        const label = new Konva.Text({
            text: text,
            fontSize: 14,
            fontFamily: 'Arial',
            fill: 'white',
            width: 190,
            padding: 10,
            align: 'center'
        });

        box.add(rect);
        box.add(label);
        this.layer.add(box);

        // Store reference
        if (type === 'problem') this.problems.push(box);
        else if (type === 'cause') this.causes.push(box);
        else this.effects.push(box);

        return box;
    }

    connectBoxes(box1, box2) {
        // Draw arrow between boxes
        const arrow = new Konva.Arrow({
            points: [
                box1.x() + 100, box1.y() + 80,
                box2.x() + 100, box2.y()
            ],
            pointerLength: 10,
            pointerWidth: 10,
            fill: '#6b7280',
            stroke: '#6b7280',
            strokeWidth: 2
        });

        this.layer.add(arrow);
        this.connections.push({arrow, box1, box2});
        arrow.moveToBottom();
    }

    exportToJSON() {
        return {
            problems: this.problems.map(p => ({
                text: p.children[1].text(),
                x: p.x(),
                y: p.y()
            })),
            causes: this.causes.map(c => ({
                text: c.children[1].text(),
                x: c.x(),
                y: c.y()
            })),
            effects: this.effects.map(e => ({
                text: e.children[1].text(),
                x: e.x(),
                y: e.y()
            })),
            connections: this.connections.map(c => ({
                from: this.problems.indexOf(c.box1),
                to: this.causes.indexOf(c.box2)
            }))
        };
    }

    async getAIFeedback() {
        const data = this.exportToJSON();

        const response = await fetch('/api/incubation-interactive/ai-feedback.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                exercise: 'problem_tree',
                data: data
            })
        });

        return await response.json();
    }
}
```

### Backend (PHP API)

```php
<?php
// /api/incubation-interactive/ai-feedback.php

require_once '../../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$exercise = $input['exercise'];
$data = $input['data'];

// Prepare AI prompt
$prompt = buildAIPrompt($exercise, $data);

// Call Claude API
$feedback = callClaudeAPI($prompt);

// Store feedback in database
storeFeedback($team_id, $exercise_id, $feedback);

echo json_encode([
    'success' => true,
    'feedback' => $feedback
]);

function buildAIPrompt($exercise, $data) {
    $base_context = file_get_contents(__DIR__ . '/../../knowledge-base/problem-tree-guide.txt');

    return "
    You are an expert incubation coach. Analyze this problem tree:

    Core Problems: " . json_encode($data['problems']) . "
    Root Causes: " . json_encode($data['causes']) . "
    Effects: " . json_encode($data['effects']) . "

    Based on the design thinking framework, provide:
    1. Completeness score (0-100%)
    2. Specific strengths
    3. Areas for improvement
    4. Actionable suggestions

    Reference: $base_context
    ";
}

function callClaudeAPI($prompt) {
    // Implementation using Claude API
    // Return AI response
}
?>
```

---

## 🚀 DEPLOYMENT PLAN

1. **Create knowledge base from orientation documents**
2. **Update database schema**
3. **Build API endpoints**
4. **Develop interactive components**
5. **Integrate AI assistant**
6. **Test with pilot team**
7. **Roll out to all teams**
8. **Gather feedback and iterate**

---

## 📝 NEXT STEPS

1. ✅ Review and approve this implementation plan
2. Begin Phase 1: Database and API foundation
3. Build first interactive exercise (Problem Tree)
4. Test with one team for feedback
5. Iterate and expand to other exercises

---

**Status:** ✅ Plan Ready for Implementation
**Estimated Timeline:** 5 weeks
**Priority Exercises:** Problem Tree, BMC, Personas, Stakeholder Map

---

**Last Updated:** November 30, 2025
