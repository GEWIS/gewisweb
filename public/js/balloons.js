function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function random(num) {
    return Math.floor(Math.random() * num)
}

function getRandomStyles() {
    const r = random(255);
    const g = random(255);
    const b = random(255);
    const mt = random(200);
    const ml = random(50);
    const dur = random(5)+10;
    const pos = random(screen.width);

    return `
        background-color: rgba(${r}, ${g}, ${b},0.7);
        color: rgba(${r}, ${g}, ${b},0.7);
        box-shadow: inset -7px -3px 10px rgba(${r - 10}, ${g - 10}, ${b - 10}, 0.7);
        margin: ${mt}px 0 0 ${ml}px;
        animation: float ${dur}s ease-in infinite;
        left: ${pos}px;
  `;
}

async function createBalloons(num) {
    const body = document.body;
    const balloonContainer = document.createElement("div");
    balloonContainer.id = 'balloon-container';
    body.append(balloonContainer);

    for (let i = num; i > 0; i--) {
        const balloon = document.createElement("div");
        balloon.className = "balloon";
        balloon.style.cssText = getRandomStyles();
        balloonContainer.append(balloon);
        await sleep(random(500));
    }
}

window.onload = function() {
    createBalloons(40);
}
