const MAX_RGB = 255;
const MAX_MARGIN_TOP = 200;
const MAX_MARGIN_LEFT = 50;
const MAX_ANIMATION_DURATION = 5;
const MIN_ANIMATION_DURATION = 10;
const MAX_SCREEN_WIDTH = screen.width;
const MAX_DELAY = 500;

const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));
const random = (num) => Math.floor(Math.random() * num);

const getRandomStyles = () => {
    const r = random(MAX_RGB);
    const g = random(MAX_RGB);
    const b = random(MAX_RGB);
    const mt = random(MAX_MARGIN_TOP);
    const ml = random(MAX_MARGIN_LEFT);
    const dur = random(MAX_ANIMATION_DURATION) + MIN_ANIMATION_DURATION;
    const pos = random(MAX_SCREEN_WIDTH);

    return `
        background-color: rgba(${r}, ${g}, ${b},0.7);
        color: rgba(${r}, ${g}, ${b},0.7);
        box-shadow: inset -7px -3px 10px rgba(${Math.max(r - 10, 0)}, ${Math.max(g - 10, 0)}, ${Math.max(b - 10, 0)}, 0.7);
        margin: ${mt}px 0 0 ${ml}px;
        animation: float ${dur}s ease-in infinite;
        left: ${pos}px;
    `;
}

const createBalloons = async (num) => {
    const balloonContainer = document.createElement('div');
    balloonContainer.id = 'balloon-container';
    document.getElementById('gewis-festivities').append(balloonContainer);

    for (let i = 0; i < num; i++) {
        const balloon = document.createElement('div');
        balloon.className = 'balloon';
        balloon.style.cssText = getRandomStyles();
        balloonContainer.append(balloon);
        await sleep(random(MAX_DELAY));
    }
}

window.onload = () => {
    createBalloons(40);
}
