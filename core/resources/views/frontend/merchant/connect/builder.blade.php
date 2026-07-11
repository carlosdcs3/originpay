@extends('frontend.merchant.connect.layout')
@section('title', 'Editor de Jornada')

@section('styles')
<style>
    .builder-container { display: flex; height: calc(100vh - 100px); background: #f8fafc; overflow: hidden; border: 1px solid #e2e8f0; border-radius: 8px;}
    .builder-sidebar { width: 250px; background: white; border-right: 1px solid #e2e8f0; padding: 15px; display: flex; flex-direction: column; gap: 10px; z-index: 10;}
    .builder-canvas-wrapper { flex: 1; position: relative; overflow: auto; background-image: radial-gradient(#cbd5e1 1px, transparent 0); background-size: 20px 20px; }
    .builder-canvas { width: 3000px; height: 3000px; position: relative; }
    
    .node-template { padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; cursor: grab; font-size: 0.85rem; font-weight: 500; display: flex; align-items: center; gap: 8px;}
    
    .flow-node { position: absolute; width: 180px; background: white; border: 2px solid #94a3b8; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 12px; cursor: move; user-select: none; z-index: 2;}
    .flow-node.trigger { border-color: #3b82f6; }
    .flow-node.action { border-color: #10b981; }
    .flow-node.delay { border-color: #f59e0b; }
    .flow-node.condition { border-color: #8b5cf6; }
    .flow-node.goal { border-color: #ec4899; }
    
    .node-header { font-weight: 600; font-size: 0.85rem; margin-bottom: 5px; pointer-events: none;}
    .node-port { width: 12px; height: 12px; background: #fff; border: 2px solid #94a3b8; border-radius: 50%; position: absolute; cursor: crosshair;}
    .node-port-out { bottom: -6px; left: 50%; transform: translateX(-50%); }
    .node-port-in { top: -6px; left: 50%; transform: translateX(-50%); }
    
    .svg-layer { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1;}
    .svg-line { fill: none; stroke: #94a3b8; stroke-width: 3; stroke-linecap: round; }
    
    .builder-topbar { height: 60px; background: white; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; }
</style>
@endsection

@section('connect_content')
<div class="trx-page-shell" style="flex: 1; padding: 0;">
    <div class="builder-topbar">
        <div>
            <h2 style="font-size: 1.1rem; margin:0;">Carrinho Abandonado V1</h2>
            <span style="font-size: 0.75rem; color: #64748b;">Draft</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="v2-btn-secondary" onclick="saveGraph()">Salvar Rascunho</button>
            <button class="v2-btn-secondary" style="border-color:#10b981; color:#10b981;" onclick="testGraph()">Testar Jornada</button>
            <button class="v2-btn-primary" onclick="publishGraph()">Publicar Versão</button>
        </div>
    </div>
    
    <div class="builder-container">
        <!-- Sidebar -->
        <div class="builder-sidebar">
            <h3 style="font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Blocos</h3>
            <div class="node-template" draggable="true" ondragstart="startDrag(event, 'trigger')">⚡ Trigger</div>
            <div class="node-template" draggable="true" ondragstart="startDrag(event, 'action')">✉️ Enviar Mensagem</div>
            <div class="node-template" draggable="true" ondragstart="startDrag(event, 'delay')">⏳ Espera (Delay)</div>
            <div class="node-template" draggable="true" ondragstart="startDrag(event, 'condition')">🔀 Condição</div>
            <div class="node-template" draggable="true" ondragstart="startDrag(event, 'goal')">🎯 Objetivo Atingido</div>
        </div>
        
        <!-- Canvas -->
        <div class="builder-canvas-wrapper">
            <div class="builder-canvas" id="canvas" ondragover="event.preventDefault()" ondrop="dropNode(event)">
                <svg class="svg-layer" id="svgLayer"></svg>
            </div>
        </div>
    </div>
</div>

<script>
    // Minimal Vanilla JS Directed Graph Builder Engine
    let nodes = [];
    let edges = [];
    let draggedNodeType = null;
    let nodeCounter = 1;
    let isDrawingEdge = false;
    let drawingSourceNodeId = null;
    
    function startDrag(e, type) {
        draggedNodeType = type;
    }
    
    function dropNode(e) {
        if (!draggedNodeType) return;
        const rect = document.getElementById('canvas').getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        createNode(draggedNodeType, x, y);
        draggedNodeType = null;
    }
    
    function createNode(type, x, y) {
        const id = 'node_' + nodeCounter++;
        const nodeObj = { id, type, x, y, data: {} };
        nodes.push(nodeObj);
        
        const div = document.createElement('div');
        div.className = `flow-node ${type}`;
        div.id = id;
        div.style.left = x + 'px';
        div.style.top = y + 'px';
        
        let title = type.charAt(0).toUpperCase() + type.slice(1);
        div.innerHTML = `
            <div class="node-header">${title}</div>
            <div class="node-port node-port-in" onmousedown="startEdge(event, '${id}', 'in')"></div>
            <div class="node-port node-port-out" onmousedown="startEdge(event, '${id}', 'out')"></div>
        `;
        
        // Simple dragging of nodes
        div.onmousedown = (e) => {
            if(e.target.classList.contains('node-port')) return;
            let startX = e.clientX;
            let startY = e.clientY;
            document.onmousemove = (me) => {
                const dx = me.clientX - startX;
                const dy = me.clientY - startY;
                nodeObj.x += dx;
                nodeObj.y += dy;
                div.style.left = nodeObj.x + 'px';
                div.style.top = nodeObj.y + 'px';
                startX = me.clientX;
                startY = me.clientY;
                drawEdges(); // Redraw lines
            };
            document.onmouseup = () => { document.onmousemove = null; document.onmouseup = null; };
        };
        
        document.getElementById('canvas').appendChild(div);
    }
    
    function startEdge(e, id, portType) {
        e.stopPropagation();
        if (portType === 'out') {
            isDrawingEdge = true;
            drawingSourceNodeId = id;
            document.onmouseup = (me) => {
                isDrawingEdge = false;
                document.onmouseup = null;
                // Check if dropped on an 'in' port of another node
                if(me.target.classList.contains('node-port-in')) {
                    const targetId = me.target.parentElement.id;
                    if(targetId !== id) {
                        edges.push({source: id, target: targetId, label: 'default'});
                        drawEdges();
                    }
                }
            };
        }
    }
    
    function drawEdges() {
        const svg = document.getElementById('svgLayer');
        svg.innerHTML = '';
        edges.forEach(edge => {
            const source = nodes.find(n => n.id === edge.source);
            const target = nodes.find(n => n.id === edge.target);
            if(source && target) {
                const x1 = source.x + 90; // Center X
                const y1 = source.y + 50; // Bottom Y
                const x2 = target.x + 90;
                const y2 = target.y; // Top Y
                
                const line = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                // Bezier curve
                const d = `M ${x1} ${y1} C ${x1} ${y1+50}, ${x2} ${y2-50}, ${x2} ${y2}`;
                line.setAttribute('d', d);
                line.setAttribute('class', 'svg-line');
                svg.appendChild(line);
            }
        });
    }

    function saveGraph() {
        const payload = { nodes, edges };
        console.log("Draft Saved:", JSON.stringify(payload));
        alert("Rascunho salvo (Ver console)");
    }

    function publishGraph() {
        // Pre-publish validations
        if (!nodes.some(n => n.type === 'trigger')) {
            alert("Aviso: Falta um nó de Trigger para iniciar a jornada!");
            return;
        }
        if (!nodes.some(n => n.type === 'goal')) {
            alert("Aviso: Falta um nó de Objetivo (Goal) para concluir a jornada!");
            return;
        }
        alert("Publicando Versão... (Grafo válido)");
    }
    
    function testGraph() {
        alert("Disparando execução de teste segura...");
    }
</script>
@endsection

