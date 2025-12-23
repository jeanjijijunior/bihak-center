/**
 * Problem Tree Interactive Module
 * Drag-and-drop visual problem tree builder
 */

let stage, layer, transformer;
let selectedBox = null;
let boxes = [];
let arrows = [];
let currentTool = null;
let connectStart = null;

function initProblemTree() {
    // Create Konva stage
    const container = document.getElementById('canvas-container');
    const width = container.offsetWidth - 4; // Account for border
    const height = 600;

    stage = new Konva.Stage({
        container: 'canvas-container',
        width: width,
        height: height
    });

    layer = new Konva.Layer();
    stage.add(layer);

    // Add transformer for selecting and editing
    transformer = new Konva.Transformer({
        nodes: [],
        keepRatio: false,
        enabledAnchors: ['middle-left', 'middle-right']
    });
    layer.add(transformer);

    // Load existing data if available
    if (existingData) {
        loadProblemTreeData(existingData);
    } else {
        // Add a sample problem box to get started
        addWelcomeMessage();
    }

    // Click on empty area to deselect
    stage.on('click', function(e) {
        if (e.target === stage) {
            transformer.nodes([]);
            selectedBox = null;
            layer.draw();
        }
    });

    // Update checklist
    updateChecklist();
}

function addWelcomeMessage() {
    const welcomeText = new Konva.Text({
        x: stage.width() / 2 - 200,
        y: stage.height() / 2 - 30,
        text: 'Click "Problem", "Cause", or "Effect" above\nto start building your problem tree',
        fontSize: 16,
        fontFamily: 'Arial',
        fill: '#9ca3af',
        width: 400,
        align: 'center'
    });
    layer.add(welcomeText);
    layer.draw();
}

function addProblemBox(type, text = '', x = null, y = null) {
    // Remove welcome message if it exists
    layer.find('Text').forEach(node => {
        if (node.text().includes('Click')) {
            node.destroy();
        }
    });

    // Default positions based on type
    if (x === null) {
        x = stage.width() / 2 - 100;
    }
    if (y === null) {
        if (type === 'problem') y = stage.height() / 2 - 40;
        else if (type === 'cause') y = stage.height() - 120;
        else if (type === 'effect') y = 50;
    }

    // Default text
    if (!text) {
        if (type === 'problem') text = 'Core Problem';
        else if (type === 'cause') text = 'Root Cause';
        else if (type === 'effect') text = 'Effect';
    }

    // Colors based on type
    const colors = {
        problem: '#ef4444', // Red
        cause: '#f59e0b',   // Orange
        effect: '#10b981'   // Green
    };

    // Create group
    const group = new Konva.Group({
        x: x,
        y: y,
        draggable: true
    });

    // Create rectangle
    const rect = new Konva.Rect({
        width: 200,
        height: 80,
        fill: colors[type],
        stroke: '#1f2937',
        strokeWidth: 2,
        cornerRadius: 8,
        shadowColor: 'black',
        shadowBlur: 10,
        shadowOpacity: 0.2,
        shadowOffset: { x: 2, y: 2 }
    });

    // Create text
    const textNode = new Konva.Text({
        text: text,
        fontSize: 14,
        fontFamily: 'Arial',
        fill: 'white',
        width: 190,
        padding: 10,
        align: 'center',
        verticalAlign: 'middle'
    });

    // Create edit icon (small circle with pencil icon)
    const editIcon = new Konva.Circle({
        x: 180,
        y: 20,
        radius: 12,
        fill: 'rgba(255, 255, 255, 0.3)',
        visible: false
    });

    group.add(rect);
    group.add(textNode);
    group.add(editIcon);

    // Store type and id
    group.setAttr('boxType', type);
    group.setAttr('boxId', 'box_' + Date.now() + '_' + Math.random());

    layer.add(group);

    // Show edit icon on hover
    group.on('mouseenter', function() {
        editIcon.visible(true);
        document.body.style.cursor = 'move';
        layer.draw();
    });

    group.on('mouseleave', function() {
        editIcon.visible(false);
        document.body.style.cursor = 'default';
        layer.draw();
    });

    // Click to select
    group.on('click', function(e) {
        e.cancelBubble = true;
        selectedBox = group;
        transformer.nodes([group]);
        layer.draw();
    });

    // Double-click to edit text
    group.on('dblclick', function() {
        editBoxText(group, textNode);
    });

    // Update arrows when dragging
    group.on('dragmove', function() {
        updateArrows(group);
    });

    boxes.push(group);
    layer.draw();
    updateChecklist();
    return group;
}

function editBoxText(group, textNode) {
    // Create textarea for editing
    const textPosition = textNode.getAbsolutePosition();
    const stageBox = stage.container().getBoundingClientRect();

    const textarea = document.createElement('textarea');
    document.body.appendChild(textarea);

    textarea.value = textNode.text();
    textarea.style.position = 'absolute';
    textarea.style.top = stageBox.top + textPosition.y + 'px';
    textarea.style.left = stageBox.left + textPosition.x + 'px';
    textarea.style.width = textNode.width() + 'px';
    textarea.style.height = textNode.height() + 'px';
    textarea.style.fontSize = '14px';
    textarea.style.padding = '10px';
    textarea.style.border = '2px solid #667eea';
    textarea.style.borderRadius = '4px';
    textarea.style.resize = 'none';
    textarea.style.zIndex = '1000';
    textarea.style.backgroundColor = 'white';

    textarea.focus();

    // Save on blur or Enter
    function removeTextarea() {
        textNode.text(textarea.value);
        document.body.removeChild(textarea);
        layer.draw();
    }

    textarea.addEventListener('blur', removeTextarea);
    textarea.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            removeTextarea();
        }
        if (e.key === 'Escape') {
            removeTextarea();
        }
    });
}

function connectBoxes(box1, box2) {
    // Calculate connection points
    const x1 = box1.x() + 100; // Center X of box1
    const y1 = box1.y() + 80;  // Bottom of box1
    const x2 = box2.x() + 100; // Center X of box2
    const y2 = box2.y();        // Top of box2

    const arrow = new Konva.Arrow({
        points: [x1, y1, x2, y2],
        pointerLength: 10,
        pointerWidth: 10,
        fill: '#6b7280',
        stroke: '#6b7280',
        strokeWidth: 3
    });

    // Store reference to connected boxes
    arrow.setAttr('startBox', box1.getAttr('boxId'));
    arrow.setAttr('endBox', box2.getAttr('boxId'));

    layer.add(arrow);
    arrow.moveToBottom();
    arrows.push(arrow);
    layer.draw();
    updateChecklist();
}

function updateArrows(movedBox) {
    const boxId = movedBox.getAttr('boxId');

    arrows.forEach(arrow => {
        const startBoxId = arrow.getAttr('startBox');
        const endBoxId = arrow.getAttr('endBox');

        if (startBoxId === boxId || endBoxId === boxId) {
            const startBox = boxes.find(b => b.getAttr('boxId') === startBoxId);
            const endBox = boxes.find(b => b.getAttr('boxId') === endBoxId);

            if (startBox && endBox) {
                const x1 = startBox.x() + 100;
                const y1 = startBox.y() + 80;
                const x2 = endBox.x() + 100;
                const y2 = endBox.y();

                arrow.points([x1, y1, x2, y2]);
            }
        }
    });

    layer.draw();
}

function deleteSelected() {
    if (!selectedBox) {
        alert('Please select a box to delete');
        return;
    }

    // Remove connected arrows
    const boxId = selectedBox.getAttr('boxId');
    arrows = arrows.filter(arrow => {
        const startBoxId = arrow.getAttr('startBox');
        const endBoxId = arrow.getAttr('endBox');

        if (startBoxId === boxId || endBoxId === boxId) {
            arrow.destroy();
            return false;
        }
        return true;
    });

    // Remove box
    boxes = boxes.filter(box => box !== selectedBox);
    selectedBox.destroy();
    selectedBox = null;
    transformer.nodes([]);

    layer.draw();
    updateChecklist();
}

function startConnecting() {
    if (!selectedBox) {
        alert('Please select the first box to connect');
        return;
    }

    connectStart = selectedBox;
    currentTool = 'connect';

    // Change cursor
    document.body.style.cursor = 'crosshair';

    // Wait for second box click
    const clickHandler = function(e) {
        if (e.target.parent && e.target.parent !== connectStart && boxes.includes(e.target.parent)) {
            connectBoxes(connectStart, e.target.parent);
            connectStart = null;
            currentTool = null;
            document.body.style.cursor = 'default';
            stage.off('click', clickHandler);
        }
    };

    stage.on('click', clickHandler);
}

function exportProblemTreeData() {
    const data = {
        boxes: boxes.map(box => {
            const textNode = box.find('Text')[0];
            return {
                id: box.getAttr('boxId'),
                type: box.getAttr('boxType'),
                text: textNode.text(),
                x: box.x(),
                y: box.y()
            };
        }),
        arrows: arrows.map(arrow => ({
            startBox: arrow.getAttr('startBox'),
            endBox: arrow.getAttr('endBox')
        }))
    };

    return data;
}

function loadProblemTreeData(data) {
    if (!data.boxes) return;

    // Clear existing
    boxes.forEach(box => box.destroy());
    arrows.forEach(arrow => arrow.destroy());
    boxes = [];
    arrows = [];

    // Load boxes
    data.boxes.forEach(boxData => {
        const box = addProblemBox(boxData.type, boxData.text, boxData.x, boxData.y);
        box.setAttr('boxId', boxData.id);
    });

    // Load arrows
    if (data.arrows) {
        data.arrows.forEach(arrowData => {
            const startBox = boxes.find(b => b.getAttr('boxId') === arrowData.startBox);
            const endBox = boxes.find(b => b.getAttr('boxId') === arrowData.endBox);

            if (startBox && endBox) {
                connectBoxes(startBox, endBox);
            }
        });
    }

    layer.draw();
    updateChecklist();
}

function updateChecklist() {
    const problemBoxes = boxes.filter(b => b.getAttr('boxType') === 'problem');
    const causeBoxes = boxes.filter(b => b.getAttr('boxType') === 'cause');
    const effectBoxes = boxes.filter(b => b.getAttr('boxType') === 'effect');

    // Update checkboxes
    updateCheckbox('problem', problemBoxes.length > 0);
    updateCheckbox('causes', causeBoxes.length >= 3);
    updateCheckbox('effects', effectBoxes.length > 0);
    updateCheckbox('connections', arrows.length > 0);
}

function updateCheckbox(checkId, checked) {
    const checkbox = document.querySelector(`.checkbox[data-check="${checkId}"]`);
    if (checkbox) {
        if (checked) {
            checkbox.classList.add('checked');
            checkbox.innerHTML = '✓';
        } else {
            checkbox.classList.remove('checked');
            checkbox.innerHTML = '';
        }
    }
}

async function exportToPDF() {
    const loadingSpinner = document.getElementById('loading-spinner');
    loadingSpinner.classList.add('active');

    try {
        // Get canvas as data URL
        const dataURL = stage.toDataURL({ pixelRatio: 2 });

        // Create PDF
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({
            orientation: 'landscape',
            unit: 'px',
            format: [stage.width(), stage.height()]
        });

        pdf.addImage(dataURL, 'PNG', 0, 0, stage.width(), stage.height());
        pdf.save(`problem-tree-team-${teamId}.pdf`);

        alert('PDF exported successfully!');
    } catch (error) {
        console.error('Export error:', error);
        alert('Failed to export PDF: ' + error.message);
    } finally {
        loadingSpinner.classList.remove('active');
    }
}

// Toolbar button handlers
document.getElementById('add-problem-btn')?.addEventListener('click', function() {
    addProblemBox('problem');
});

document.getElementById('add-cause-btn')?.addEventListener('click', function() {
    addProblemBox('cause');
});

document.getElementById('add-effect-btn')?.addEventListener('click', function() {
    addProblemBox('effect');
});

document.getElementById('add-arrow-btn')?.addEventListener('click', function() {
    startConnecting();
});

document.getElementById('delete-btn')?.addEventListener('click', function() {
    deleteSelected();
});

// Export handler
document.getElementById('export-pdf-btn')?.addEventListener('click', function() {
    exportToPDF();
});

// Save draft handler
document.getElementById('save-draft-btn')?.addEventListener('click', async function() {
    const data = exportProblemTreeData();
    await saveDraft(data);
});

async function saveDraft(data) {
    const loadingSpinner = document.getElementById('loading-spinner');
    loadingSpinner.classList.add('active');

    try {
        const response = await fetch('/api/incubation-interactive/save-data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                team_id: teamId,
                exercise_id: exerciseId,
                data_type: 'problem_tree',
                data_json: data
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('Draft saved successfully!');
        } else {
            alert('Failed to save draft: ' + result.message);
        }
    } catch (error) {
        console.error('Save error:', error);
        alert('Failed to save draft: ' + error.message);
    } finally {
        loadingSpinner.classList.remove('active');
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Delete' && selectedBox) {
        deleteSelected();
    }
    if (e.key === 'Escape') {
        transformer.nodes([]);
        selectedBox = null;
        connectStart = null;
        currentTool = null;
        document.body.style.cursor = 'default';
        layer.draw();
    }
});
