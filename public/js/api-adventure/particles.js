/* ========================================================================
   Request packets and the arrival burst.

   Both use one preallocated buffer each. Nothing is created per frame and the
   counts are fixed at boot, so the particle load is the same on the last
   replay as on the first — there is no path here that grows over time.
   ======================================================================== */

import * as THREE from '../vendor/three.module.min.js';

/** A soft round sprite, generated once and shared by every packet system. */
let dotTexture = null;

function sharedDot() {
    if (dotTexture) return dotTexture;

    const size = 64;
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = size;

    const ctx = canvas.getContext('2d');
    const gradient = ctx.createRadialGradient(size / 2, size / 2, 0, size / 2, size / 2, size / 2);
    gradient.addColorStop(0, 'rgba(255,255,255,1)');
    gradient.addColorStop(0.35, 'rgba(255,255,255,0.55)');
    gradient.addColorStop(1, 'rgba(255,255,255,0)');
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, size, size);

    dotTexture = new THREE.CanvasTexture(canvas);

    return dotTexture;
}

/**
 * A short trail of packets riding a curve, led by the request head.
 *
 * This is the site's original animated dot, promoted: it still leads the
 * journey, it still changes colour at each checkpoint, and it now travels the
 * pipe geometry rather than a straight CSS connector.
 */
export class Packets {
    /** @param {number} count how many dots make the trail */
    constructor(count = 9) {
        this.count = count;
        this.spacing = 0.018;

        const positions = new Float32Array(count * 3);
        const sizes = new Float32Array(count);
        const alphas = new Float32Array(count);

        for (let i = 0; i < count; i++) {
            sizes[i] = 13 - i * 0.85;
            alphas[i] = 1 - i / count;
        }

        this.geometry = new THREE.BufferGeometry();
        this.geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        this.geometry.setAttribute('size', new THREE.BufferAttribute(sizes, 1));
        this.geometry.setAttribute('alpha', new THREE.BufferAttribute(alphas, 1));

        this.material = new THREE.ShaderMaterial({
            uniforms: {
                uMap: { value: sharedDot() },
                uColour: { value: new THREE.Color(0x3d7fff) },
            },
            vertexShader: `
                attribute float size;
                attribute float alpha;
                varying float vAlpha;
                void main() {
                    vAlpha = alpha;
                    gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
                    gl_PointSize = size;
                }
            `,
            fragmentShader: `
                uniform sampler2D uMap;
                uniform vec3 uColour;
                varying float vAlpha;
                void main() {
                    vec4 tex = texture2D(uMap, gl_PointCoord);
                    gl_FragColor = vec4(uColour + tex.a * 0.5, tex.a * vAlpha);
                }
            `,
            transparent: true,
            depthWrite: false,
            blending: THREE.AdditiveBlending,
        });

        this.points = new THREE.Points(this.geometry, this.material);
        this.points.renderOrder = 8;
        this.points.frustumCulled = false;
    }

    /**
     * @param {THREE.Curve} curve the path to ride
     * @param {number} head 0..1 position of the leading packet
     */
    follow(curve, head) {
        const position = this.geometry.attributes.position;

        for (let i = 0; i < this.count; i++) {
            const u = Math.max(0, Math.min(1, head - i * this.spacing));
            const point = curve.getPointAt(u);
            position.setXYZ(i, point.x, point.y, 0.5);
        }

        position.needsUpdate = true;
    }

    setTone(hex) {
        this.material.uniforms.uColour.value.setHex(hex);
    }

    set visible(value) {
        this.points.visible = value;
    }

    dispose() {
        this.geometry.dispose();
        this.material.dispose();
    }
}

/**
 * The arrival burst at the portal. Fixed particle count, replayed by resetting
 * the clock rather than by allocating a new system.
 */
export class Burst {
    constructor(count = 34) {
        this.count = count;
        this.life = -1;

        this.origin = new THREE.Vector3();
        this.velocity = new Float32Array(count * 2);

        for (let i = 0; i < count; i++) {
            const angle = (i / count) * Math.PI * 2 + Math.random() * 0.3;
            const speed = 90 + Math.random() * 150;
            this.velocity[i * 2] = Math.cos(angle) * speed;
            this.velocity[i * 2 + 1] = Math.sin(angle) * speed;
        }

        const positions = new Float32Array(count * 3);
        const sizes = new Float32Array(count).fill(9);

        this.geometry = new THREE.BufferGeometry();
        this.geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        this.geometry.setAttribute('size', new THREE.BufferAttribute(sizes, 1));

        this.material = new THREE.PointsMaterial({
            map: sharedDot(),
            size: 10,
            sizeAttenuation: false,
            color: 0x8fc4ff,
            transparent: true,
            depthWrite: false,
            blending: THREE.AdditiveBlending,
        });

        this.points = new THREE.Points(this.geometry, this.material);
        this.points.visible = false;
        this.points.renderOrder = 11;
        this.points.frustumCulled = false;
    }

    fire(x, y) {
        this.origin.set(x, y, 0);
        this.life = 0;
        this.points.visible = true;
    }

    update(dt) {
        if (this.life < 0) return;

        this.life += dt;

        if (this.life > 1.1) {
            this.life = -1;
            this.points.visible = false;

            return;
        }

        const position = this.geometry.attributes.position;
        const eased = 1 - Math.pow(1 - Math.min(this.life, 1), 3);

        for (let i = 0; i < this.count; i++) {
            position.setXYZ(
                i,
                this.origin.x + this.velocity[i * 2] * eased * 0.5,
                this.origin.y + this.velocity[i * 2 + 1] * eased * 0.5,
                0.6,
            );
        }

        position.needsUpdate = true;
        this.material.opacity = 1 - this.life / 1.1;
    }

    reset() {
        this.life = -1;
        this.points.visible = false;
    }

    dispose() {
        this.geometry.dispose();
        this.material.dispose();
    }
}
