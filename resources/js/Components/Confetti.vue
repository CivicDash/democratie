<template>
    <Teleport to="body">
        <div v-if="show" class="confetti-canvas">
            <canvas ref="canvas"></canvas>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    duration: {
        type: Number,
        default: 3000,
    },
    particleCount: {
        type: Number,
        default: 150,
    },
});

const emit = defineEmits(['complete']);

const canvas = ref(null);
let ctx = null;
let particles = [];
let animationFrame = null;

class Particle {
    constructor(x, y) {
        this.x = x;
        this.y = y;
        this.size = Math.random() * 8 + 4;
        this.speedX = Math.random() * 6 - 3;
        this.speedY = Math.random() * -15 - 5;
        this.gravity = 0.5;
        this.friction = 0.98;
        this.opacity = 1;
        this.rotation = Math.random() * 360;
        this.rotationSpeed = Math.random() * 10 - 5;
        
        const colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', 
            '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2',
            '#F39C12', '#E74C3C', '#9B59B6', '#3498DB',
        ];
        this.color = colors[Math.floor(Math.random() * colors.length)];
        
        // Random shapes
        this.shape = Math.random() > 0.5 ? 'square' : 'circle';
    }
    
    update() {
        this.speedY += this.gravity;
        this.x += this.speedX;
        this.y += this.speedY;
        this.speedX *= this.friction;
        this.speedY *= this.friction;
        this.rotation += this.rotationSpeed;
        
        if (this.y > window.innerHeight) {
            this.opacity -= 0.02;
        }
    }
    
    draw(ctx) {
        ctx.save();
        ctx.globalAlpha = this.opacity;
        ctx.translate(this.x, this.y);
        ctx.rotate((this.rotation * Math.PI) / 180);
        ctx.fillStyle = this.color;
        
        if (this.shape === 'circle') {
            ctx.beginPath();
            ctx.arc(0, 0, this.size / 2, 0, Math.PI * 2);
            ctx.fill();
        } else {
            ctx.fillRect(-this.size / 2, -this.size / 2, this.size, this.size);
        }
        
        ctx.restore();
    }
}

const initCanvas = () => {
    if (!canvas.value) return;
    
    canvas.value.width = window.innerWidth;
    canvas.value.height = window.innerHeight;
    ctx = canvas.value.getContext('2d');
};

const createParticles = () => {
    particles = [];
    const centerX = window.innerWidth / 2;
    const centerY = window.innerHeight / 3;
    
    for (let i = 0; i < props.particleCount; i++) {
        const angle = (Math.PI * 2 * i) / props.particleCount;
        const velocity = Math.random() * 5 + 5;
        const x = centerX + Math.cos(angle) * (Math.random() * 50);
        const y = centerY + Math.sin(angle) * (Math.random() * 50);
        
        const particle = new Particle(x, y);
        particle.speedX = Math.cos(angle) * velocity;
        particle.speedY = Math.sin(angle) * velocity - 10;
        
        particles.push(particle);
    }
};

const animate = () => {
    if (!ctx) return;
    
    ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
    
    particles = particles.filter(p => p.opacity > 0);
    
    particles.forEach(particle => {
        particle.update();
        particle.draw(ctx);
    });
    
    if (particles.length > 0) {
        animationFrame = requestAnimationFrame(animate);
    } else {
        emit('complete');
    }
};

const start = () => {
    initCanvas();
    createParticles();
    animate();
    
    setTimeout(() => {
        // Fade out after duration
        particles.forEach(p => {
            p.opacity = Math.max(0, p.opacity - 0.02);
        });
    }, props.duration);
};

const stop = () => {
    if (animationFrame) {
        cancelAnimationFrame(animationFrame);
        animationFrame = null;
    }
    particles = [];
    if (ctx && canvas.value) {
        ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
    }
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        start();
    } else {
        stop();
    }
});

onMounted(() => {
    if (props.show) {
        start();
    }
    
    window.addEventListener('resize', () => {
        if (canvas.value) {
            initCanvas();
        }
    });
});
</script>

<style scoped>
.confetti-canvas {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 10000;
}

canvas {
    width: 100%;
    height: 100%;
}
</style>

