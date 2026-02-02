import { useEffect, useRef, useCallback, useState } from "react";
import gsap from "gsap";
import { MotionPathPlugin } from "gsap/MotionPathPlugin";

// Register GSAP plugins at module load time
gsap.registerPlugin(MotionPathPlugin);

interface ArcheryGameProps {
    onScore?: (score: number, type: "bullseye" | "hit" | "miss") => void;
    onGameEnd?: (finalScore: number) => void;
    disabled?: boolean;
    maxArrows?: number;
}

const DEFAULT_MAX_ARROWS = 10;

export function ArcheryGame({
    onScore,
    onGameEnd,
    disabled = false,
    maxArrows = DEFAULT_MAX_ARROWS,
}: ArcheryGameProps) {
    const svgRef = useRef<SVGSVGElement>(null);
    const arrowsRef = useRef<SVGGElement>(null);
    const arcRef = useRef<SVGPathElement>(null);
    const bowRef = useRef<SVGGElement>(null);
    const bowPolylineRef = useRef<SVGPolylineElement>(null);
    const arrowAngleRef = useRef<SVGGElement>(null);
    const arrowUseRef = useRef<SVGUseElement>(null);
    const missRef = useRef<SVGGElement>(null);
    const hitRef = useRef<SVGGElement>(null);
    const bullseyeRef = useRef<SVGGElement>(null);
    const targetRef = useRef<SVGGElement>(null);

    const [arrowCount, setArrowCount] = useState(0);
    const [score, setScore] = useState(0);
    const [lastShot, setLastShot] = useState<"bullseye" | "hit" | "miss" | null>(null);
    const [lastShotScore, setLastShotScore] = useState(0);
    const [gameEnded, setGameEnded] = useState(false);
    const [gameStarted, setGameStarted] = useState(false);
    const [timeRemaining, setTimeRemaining] = useState(60); // 1 minute timer

    const randomAngleRef = useRef(0);
    const isDrawingRef = useRef(false);
    const targetOffsetRef = useRef(0); // Track target's Y offset for hit detection
    const gameEndedRef = useRef(false); // Prevent double-calling onGameEnd
    const scoreRef = useRef(0); // Track score for endGame callback
    const timerRef = useRef<NodeJS.Timeout | null>(null);

    // End game handler
    const endGame = useCallback(() => {
        if (gameEndedRef.current) return;
        gameEndedRef.current = true;
        setGameEnded(true);
        if (timerRef.current) {
            clearInterval(timerRef.current);
            timerRef.current = null;
        }
        onGameEnd?.(scoreRef.current);
    }, [onGameEnd]);

    // Start game handler
    const startGame = useCallback(() => {
        if (disabled || gameStarted) return;
        setGameStarted(true);
        setTimeRemaining(60);

        // Start countdown timer
        timerRef.current = setInterval(() => {
            setTimeRemaining((prev) => {
                if (prev <= 1) {
                    // Time's up - end game
                    if (timerRef.current) {
                        clearInterval(timerRef.current);
                        timerRef.current = null;
                    }
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);
    }, [disabled, gameStarted]);

    // End game when timer hits 0
    useEffect(() => {
        if (gameStarted && timeRemaining === 0 && !gameEndedRef.current) {
            const timeout = setTimeout(() => {
                endGame();
            }, 500);
            return () => clearTimeout(timeout);
        }
    }, [gameStarted, timeRemaining, endGame]);

    // Cleanup timer on unmount
    useEffect(() => {
        return () => {
            if (timerRef.current) {
                clearInterval(timerRef.current);
            }
        };
    }, []);

    // Update scoreRef when score changes
    useEffect(() => {
        scoreRef.current = score;
    }, [score]);

    // Check for max arrows reached
    useEffect(() => {
        if (arrowCount >= maxArrows && !gameEndedRef.current) {
            // Small delay to let the final shot animation complete
            const timeout = setTimeout(() => {
                endGame();
            }, 1000);
            return () => clearTimeout(timeout);
        }
    }, [arrowCount, maxArrows, endGame]);

    // Constants
    const baseTarget = { x: 900, y: 249.5 };
    const baseLineSegment = { x1: 875, y1: 280, x2: 925, y2: 220 };
    const pivot = { x: 100, y: 250 };

    const getMouseSVG = useCallback((e: MouseEvent | React.MouseEvent) => {
        if (!svgRef.current) return { x: 0, y: 0 };
        const svg = svgRef.current;
        const point = svg.createSVGPoint();
        point.x = e.clientX;
        point.y = e.clientY;
        const ctm = svg.getScreenCTM();
        if (!ctm) return { x: 0, y: 0 };
        return point.matrixTransform(ctm.inverse());
    }, []);

    const getIntersection = useCallback(
        (
            segment1: { x1: number; y1: number; x2: number; y2: number },
            segment2: { x1: number; y1: number; x2: number; y2: number },
        ) => {
            const dx1 = segment1.x2 - segment1.x1;
            const dy1 = segment1.y2 - segment1.y1;
            const dx2 = segment2.x2 - segment2.x1;
            const dy2 = segment2.y2 - segment2.y1;
            const cx = segment1.x1 - segment2.x1;
            const cy = segment1.y1 - segment2.y1;
            const denominator = dy2 * dx1 - dx2 * dy1;
            if (denominator === 0) return null;
            const ua = (dx2 * cy - dy2 * cx) / denominator;
            const ub = (dx1 * cy - dy1 * cx) / denominator;
            return {
                x: segment1.x1 + ua * dx1,
                y: segment1.y1 + ua * dy1,
                segment1: ua >= 0 && ua <= 1,
                segment2: ub >= 0 && ub <= 1,
            };
        },
        [],
    );

    const showMessage = useCallback((ref: React.RefObject<SVGGElement | null>) => {
        if (!ref.current) return;
        const element = ref.current;
        gsap.killTweensOf(element);
        gsap.killTweensOf(element.querySelectorAll("path"));
        gsap.set(element, { autoAlpha: 1 });
        gsap.fromTo(
            element.querySelectorAll("path"),
            { rotation: -5, scale: 0, transformOrigin: "center" },
            { scale: 1, ease: "back.out", stagger: 0.05, duration: 0.5 },
        );
        gsap.to(element.querySelectorAll("path"), {
            delay: 2,
            rotation: 20,
            scale: 0,
            ease: "back.in",
            stagger: 0.03,
            duration: 0.3,
        });
    }, []);

    const aim = useCallback(
        (e: MouseEvent | React.MouseEvent) => {
            if (
                !bowRef.current ||
                !bowPolylineRef.current ||
                !arrowAngleRef.current ||
                !arrowUseRef.current ||
                !arcRef.current
            )
                return;

            const point = getMouseSVG(e);
            point.x = Math.min(point.x, pivot.x - 7);
            point.y = Math.max(point.y, pivot.y + 7);

            const dx = point.x - pivot.x;
            const dy = point.y - pivot.y;
            const angle = Math.atan2(dy, dx) + randomAngleRef.current;
            const bowAngle = angle - Math.PI;
            const distance = Math.min(Math.sqrt(dx * dx + dy * dy), 50);
            const scale = Math.min(Math.max(distance / 30, 1), 2);

            gsap.to(bowRef.current, {
                scaleX: scale,
                rotation: (bowAngle * 180) / Math.PI,
                transformOrigin: "right center",
                duration: 0.3,
            });

            gsap.to(arrowAngleRef.current, {
                rotation: (bowAngle * 180) / Math.PI,
                svgOrigin: "100 250",
                duration: 0.3,
            });

            gsap.to(arrowUseRef.current, {
                x: -distance,
                duration: 0.3,
            });

            const arrowX = Math.min(pivot.x - (1 / scale) * distance, 88);
            gsap.to(bowPolylineRef.current, {
                attr: { points: `88,200 ${arrowX},250 88,300` },
                duration: 0.3,
            });

            const radius = distance * 7.5;
            const offset = {
                x: Math.cos(bowAngle) * radius,
                y: Math.sin(bowAngle) * radius,
            };
            const arcWidth = offset.x * 2.8;

            gsap.to(arcRef.current, {
                attr: {
                    d: `M100,250c${offset.x},${offset.y},${arcWidth - offset.x},${offset.y + 50},${arcWidth},50`,
                },
                autoAlpha: distance / 60,
                duration: 0.3,
            });
        },
        [getMouseSVG],
    );

    const loose = useCallback(() => {
        if (
            !bowRef.current ||
            !bowPolylineRef.current ||
            !arrowsRef.current ||
            !arcRef.current ||
            !arrowUseRef.current
        )
            return;

        isDrawingRef.current = false;

        gsap.to(bowRef.current, {
            scaleX: 1,
            transformOrigin: "right center",
            ease: "elastic.out",
            duration: 0.4,
        });

        gsap.to(bowPolylineRef.current, {
            attr: { points: "88,200 88,250 88,300" },
            ease: "elastic.out",
            duration: 0.4,
        });

        // Create new arrow
        const newArrow = document.createElementNS("http://www.w3.org/2000/svg", "use");
        newArrow.setAttributeNS("http://www.w3.org/1999/xlink", "href", "#arrow");
        arrowsRef.current.appendChild(newArrow);
        setArrowCount((prev) => prev + 1);

        // Get path length for position calculation
        const arcPath = arcRef.current;
        const pathLength = arcPath.getTotalLength();
        let hasHit = false;
        const duration = 0.5;
        const startTime = Date.now();

        // Set initial position
        const startPoint = arcPath.getPointAtLength(0);
        const nextPoint = arcPath.getPointAtLength(1);
        const startAngle = Math.atan2(nextPoint.y - startPoint.y, nextPoint.x - startPoint.x);

        gsap.set(newArrow, {
            x: startPoint.x,
            y: startPoint.y,
            rotation: (startAngle * 180) / Math.PI,
        });

        // Custom animation that manually updates position along path
        const animate = () => {
            if (hasHit) return;

            const elapsed = (Date.now() - startTime) / 1000;
            const progress = Math.min(elapsed / duration, 1);

            // Get position on path
            const currentLength = progress * pathLength;
            const point = arcPath.getPointAtLength(currentLength);

            // Get rotation from path tangent
            const nextLength = Math.min(currentLength + 1, pathLength);
            const tangentPoint = arcPath.getPointAtLength(nextLength);
            const angle = Math.atan2(tangentPoint.y - point.y, tangentPoint.x - point.x);
            const rotationDeg = (angle * 180) / Math.PI;

            // Update arrow position and rotation
            gsap.set(newArrow, {
                x: point.x,
                y: point.y,
                rotation: rotationDeg,
            });

            // Hit test - account for moving target
            const currentTargetOffset = targetOffsetRef.current;
            const currentLineSegment = {
                x1: baseLineSegment.x1,
                y1: baseLineSegment.y1 + currentTargetOffset,
                x2: baseLineSegment.x2,
                y2: baseLineSegment.y2 + currentTargetOffset,
            };
            const currentTarget = {
                x: baseTarget.x,
                y: baseTarget.y + currentTargetOffset,
            };

            const arrowSegment = {
                x1: point.x,
                y1: point.y,
                x2: Math.cos(angle) * 60 + point.x,
                y2: Math.sin(angle) * 60 + point.y,
            };

            const intersection = getIntersection(arrowSegment, currentLineSegment);
            if (intersection && intersection.segment1 && intersection.segment2) {
                hasHit = true;

                const dx = intersection.x - currentTarget.x;
                const dy = intersection.y - currentTarget.y;
                const distance = Math.sqrt(dx * dx + dy * dy);

                // Attach arrow to target so it moves with it
                // Adjust position to be relative to target (subtract offset)
                if (targetRef.current) {
                    gsap.set(newArrow, {
                        x: point.x,
                        y: point.y - currentTargetOffset, // Position relative to target
                    });
                    targetRef.current.appendChild(newArrow);
                }

                // Calculate score based on distance from center (max ~35 pixels)
                const hitScore = Math.max(10, Math.round(100 - distance * 2.5));

                // Create floating score text
                if (svgRef.current) {
                    const scoreText = document.createElementNS(
                        "http://www.w3.org/2000/svg",
                        "text",
                    );
                    scoreText.textContent = `+${hitScore}`;
                    scoreText.setAttribute("x", String(baseTarget.x));
                    scoreText.setAttribute("y", String(baseTarget.y + currentTargetOffset - 50));
                    scoreText.setAttribute("text-anchor", "middle");
                    scoreText.setAttribute("font-family", "monospace");
                    scoreText.setAttribute("font-size", "24");
                    scoreText.setAttribute("font-weight", "bold");

                    // Color: green (high score) to yellow (low score)
                    // Score ranges from ~10 to 100, normalize to 0-1
                    const scoreRatio = (hitScore - 10) / 90;
                    // Interpolate: yellow (rgb 234, 179, 8) to green (rgb 34, 197, 94)
                    const r = Math.round(234 - scoreRatio * (234 - 34));
                    const g = Math.round(179 + scoreRatio * (197 - 179));
                    const b = Math.round(8 + scoreRatio * (94 - 8));
                    scoreText.setAttribute("fill", `rgb(${r}, ${g}, ${b})`);

                    svgRef.current.appendChild(scoreText);

                    // Animate floating up and fading out
                    gsap.fromTo(
                        scoreText,
                        { opacity: 0, y: baseTarget.y + currentTargetOffset - 50 },
                        {
                            opacity: 1,
                            y: baseTarget.y + currentTargetOffset - 100,
                            duration: 0.3,
                            ease: "power2.out",
                            onComplete: () => {
                                gsap.to(scoreText, {
                                    opacity: 0,
                                    y: baseTarget.y + currentTargetOffset - 150,
                                    duration: 0.5,
                                    delay: 0.3,
                                    ease: "power2.in",
                                    onComplete: () => {
                                        scoreText.remove();
                                    },
                                });
                            },
                        },
                    );
                }

                if (distance < 7) {
                    setLastShot("bullseye");
                    setLastShotScore(hitScore);
                    setScore((prev) => prev + hitScore);
                    showMessage(bullseyeRef);
                    onScore?.(hitScore, "bullseye");
                } else {
                    setLastShot("hit");
                    setLastShotScore(hitScore);
                    setScore((prev) => prev + hitScore);
                    showMessage(hitRef);
                    onScore?.(hitScore, "hit");
                }
                return;
            }

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                // Animation complete - miss, arrow falls to ground
                const groundY = 340;
                const finalX = point.x;
                const finalY = point.y;

                // Animate arrow falling to ground
                gsap.to(newArrow, {
                    y: groundY,
                    rotation: 45 + Math.random() * 30, // Random angle pointing into ground
                    duration: 0.4,
                    ease: "power2.in",
                });

                setLastShot("miss");
                setLastShotScore(0);
                showMessage(missRef);
                onScore?.(0, "miss");
            }
        };

        requestAnimationFrame(animate);

        gsap.to(arcRef.current, { opacity: 0, duration: 0.3 });
        gsap.set(arrowUseRef.current, { opacity: 0 });
    }, [getIntersection, onScore, showMessage]);

    const draw = useCallback(
        (e: MouseEvent | React.MouseEvent) => {
            // Don't allow drawing if disabled, game not started, or game ended
            if (disabled || !gameStarted || gameEnded) return;

            // Only allow drawing if clicking near the bow string area
            const point = getMouseSVG(e);
            const bowStringX = 88;
            const bowStringYMin = 190;
            const bowStringYMax = 310;
            const clickRadius = 40; // How close to the string you need to click

            const nearString =
                point.x >= bowStringX - clickRadius &&
                point.x <= bowStringX + clickRadius &&
                point.y >= bowStringYMin &&
                point.y <= bowStringYMax;

            if (!nearString) return;

            randomAngleRef.current = Math.random() * Math.PI * 0.03 - 0.015;
            isDrawingRef.current = true;

            if (arrowUseRef.current) {
                gsap.to(arrowUseRef.current, { opacity: 1, duration: 0.3 });
            }

            aim(e);
        },
        [aim, getMouseSVG, disabled, gameStarted, gameEnded],
    );

    useEffect(() => {
        const handleMouseMove = (e: MouseEvent) => {
            if (isDrawingRef.current) {
                aim(e);
            }
        };

        const handleMouseUp = () => {
            if (isDrawingRef.current) {
                loose();
            }
        };

        window.addEventListener("mousemove", handleMouseMove);
        window.addEventListener("mouseup", handleMouseUp);

        // Initial aim position
        aim({ clientX: 320, clientY: 300 } as MouseEvent);

        return () => {
            window.removeEventListener("mousemove", handleMouseMove);
            window.removeEventListener("mouseup", handleMouseUp);
        };
    }, [aim, loose]);

    // Animate target moving up and down
    useEffect(() => {
        if (!targetRef.current) return;

        const tl = gsap.timeline({ repeat: -1, yoyo: true });
        tl.to(targetRef.current, {
            y: -60,
            duration: 1.5,
            ease: "sine.inOut",
            onUpdate: function () {
                targetOffsetRef.current = gsap.getProperty(targetRef.current, "y") as number;
            },
        });
        tl.to(targetRef.current, {
            y: 60,
            duration: 1.5,
            ease: "sine.inOut",
            onUpdate: function () {
                targetOffsetRef.current = gsap.getProperty(targetRef.current, "y") as number;
            },
        });

        return () => {
            tl.kill();
        };
    }, []);

    return (
        <div className="relative w-full rounded-xl border-2 border-amber-600/30 bg-gradient-to-b from-stone-900 to-stone-950 overflow-hidden">
            {/* Score display */}
            <div className="absolute top-4 left-4 z-10 flex items-center gap-4">
                <div className="rounded-lg border border-amber-600/50 bg-stone-900/90 px-4 py-2">
                    <span className="font-pixel text-xs text-stone-400">Score</span>
                    <p className="font-pixel text-2xl text-amber-300">{score}</p>
                </div>
                <div className="rounded-lg border border-stone-600/50 bg-stone-900/90 px-4 py-2">
                    <span className="font-pixel text-xs text-stone-400">Arrows</span>
                    <p className="font-pixel text-2xl text-stone-300">
                        {arrowCount}/{maxArrows}
                    </p>
                </div>
                {gameStarted && !gameEnded && (
                    <div
                        className={`rounded-lg border px-4 py-2 ${
                            timeRemaining <= 10
                                ? "border-red-500/50 bg-red-900/50"
                                : "border-stone-600/50 bg-stone-900/90"
                        }`}
                    >
                        <span className="font-pixel text-xs text-stone-400">Time</span>
                        <p
                            className={`font-pixel text-2xl ${
                                timeRemaining <= 10 ? "text-red-400" : "text-stone-300"
                            }`}
                        >
                            {Math.floor(timeRemaining / 60)}:
                            {String(timeRemaining % 60).padStart(2, "0")}
                        </p>
                    </div>
                )}
                {lastShot && (
                    <div
                        className={`rounded-lg border px-4 py-2 ${
                            lastShot === "bullseye"
                                ? "border-red-500/50 bg-red-900/50"
                                : lastShot === "hit"
                                  ? "border-amber-500/50 bg-amber-900/50"
                                  : "border-stone-500/50 bg-stone-800/50"
                        }`}
                    >
                        <span className="font-pixel text-xs text-stone-400">Last Shot</span>
                        <div className="flex items-baseline gap-2">
                            <p
                                className={`font-pixel text-lg ${
                                    lastShot === "bullseye"
                                        ? "text-red-400"
                                        : lastShot === "hit"
                                          ? "text-amber-400"
                                          : "text-stone-400"
                                }`}
                            >
                                {lastShot === "bullseye"
                                    ? "BULLSEYE!"
                                    : lastShot === "hit"
                                      ? "HIT!"
                                      : "MISS"}
                            </p>
                            <span
                                className={`font-pixel text-sm ${
                                    lastShot === "bullseye"
                                        ? "text-red-300"
                                        : lastShot === "hit"
                                          ? "text-amber-300"
                                          : "text-stone-500"
                                }`}
                            >
                                +{lastShotScore}
                            </span>
                        </div>
                    </div>
                )}
            </div>

            {/* Instructions and End Game button */}
            <div className="absolute bottom-4 left-4 right-4 z-10 flex items-center justify-between">
                <p className="font-pixel text-xs text-stone-500">
                    {gameEnded
                        ? "Game Over! Final score recorded."
                        : disabled
                          ? "Game unavailable"
                          : !gameStarted
                            ? "Click Start Game to begin!"
                            : "Click and drag the bow string to shoot!"}
                </p>
                {!disabled && gameStarted && !gameEnded && arrowCount > 0 && (
                    <button
                        onClick={endGame}
                        className="rounded-lg border border-amber-500/50 bg-amber-900/50 px-4 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-800/50"
                    >
                        End Game
                    </button>
                )}
            </div>

            {/* How to Play / Start Game overlay */}
            {!disabled && !gameStarted && (
                <div className="absolute inset-0 z-20 flex items-center justify-center bg-stone-900/80">
                    <div className="rounded-xl border-2 border-amber-600/50 bg-stone-900/95 p-8 text-center max-w-md">
                        <h2 className="font-pixel text-2xl text-amber-300 mb-4">How to Play</h2>
                        <div className="space-y-3 text-left mb-6">
                            <div className="flex items-start gap-3">
                                <span className="font-pixel text-amber-500">1.</span>
                                <p className="text-sm text-stone-300">
                                    Click and hold on the bow string (left side)
                                </p>
                            </div>
                            <div className="flex items-start gap-3">
                                <span className="font-pixel text-amber-500">2.</span>
                                <p className="text-sm text-stone-300">
                                    Drag down and left to aim - the further you pull, the more power
                                </p>
                            </div>
                            <div className="flex items-start gap-3">
                                <span className="font-pixel text-amber-500">3.</span>
                                <p className="text-sm text-stone-300">
                                    Release to shoot! Hit the moving target for points
                                </p>
                            </div>
                            <div className="flex items-start gap-3">
                                <span className="font-pixel text-amber-500">4.</span>
                                <p className="text-sm text-stone-300">
                                    You have <span className="text-amber-400">1 minute</span> and{" "}
                                    <span className="text-amber-400">{maxArrows} arrows</span>
                                </p>
                            </div>
                        </div>
                        <div className="space-y-2 mb-6 text-xs">
                            <p className="text-stone-400">Scoring:</p>
                            <div className="flex justify-center gap-4">
                                <span className="text-red-400">Bullseye ~100</span>
                                <span className="text-amber-400">Hit ~50</span>
                                <span className="text-stone-500">Miss 0</span>
                            </div>
                        </div>
                        <button
                            onClick={startGame}
                            className="rounded-lg border-2 border-amber-500 bg-amber-600 px-8 py-3 font-pixel text-lg text-white transition hover:bg-amber-500 hover:scale-105"
                        >
                            Start Game
                        </button>
                    </div>
                </div>
            )}

            {/* Disabled overlay */}
            {disabled && (
                <div className="absolute inset-0 z-20 flex items-center justify-center bg-stone-900/70">
                    <div className="rounded-xl border-2 border-amber-600/50 bg-stone-900/90 p-6 text-center">
                        <p className="font-pixel text-xl text-amber-300">Archery Unavailable</p>
                        <p className="mt-2 font-pixel text-sm text-stone-400">
                            Come back tomorrow to play again!
                        </p>
                    </div>
                </div>
            )}

            {/* Game Over overlay */}
            {gameEnded && (
                <div className="absolute inset-0 z-20 flex items-center justify-center bg-stone-900/70">
                    <div className="rounded-xl border-2 border-amber-600/50 bg-stone-900/90 p-6 text-center">
                        <p className="font-pixel text-xl text-amber-300">Game Over!</p>
                        <p className="mt-2 font-pixel text-2xl text-amber-400">
                            Final Score: {score}
                        </p>
                        <p className="mt-2 font-pixel text-sm text-stone-400">
                            You fired {arrowCount} arrow{arrowCount !== 1 ? "s" : ""} in{" "}
                            {60 - timeRemaining} seconds
                        </p>
                    </div>
                </div>
            )}

            <svg
                ref={svgRef}
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 1000 400"
                overflow="visible"
                className="w-full h-[400px] cursor-crosshair"
                onMouseDown={(e) => draw(e)}
            >
                <linearGradient id="ArcGradient">
                    <stop offset="0" stopColor="#f59e0b" stopOpacity=".4" />
                    <stop offset="50%" stopColor="#f59e0b" stopOpacity="0" />
                </linearGradient>

                {/* Background elements */}
                <rect width="1000" height="400" fill="transparent" />

                {/* Ground line */}
                <line x1="0" y1="350" x2="1000" y2="350" stroke="#44403c" strokeWidth="2" />

                {/* Arc trajectory preview */}
                <path
                    ref={arcRef}
                    id="arc"
                    fill="none"
                    stroke="url(#ArcGradient)"
                    strokeWidth="4"
                    d="M100,250c250-400,550-400,800,0"
                    pointerEvents="none"
                />

                <defs>
                    <g id="arrow">
                        <line x2="60" fill="none" stroke="#78716c" strokeWidth="2" />
                        <polygon fill="#78716c" points="64 0 58 2 56 0 58 -2" />
                        <polygon fill="#f59e0b" points="2 -3 -4 -3 -1 0 -4 3 2 3 5 0" />
                    </g>
                </defs>

                {/* Target */}
                <g ref={targetRef} id="target">
                    <path
                        fill="#FFF"
                        d="M924.2,274.2c-21.5,21.5-45.9,19.9-52,3.2c-4.4-12.1,2.4-29.2,14.2-41c11.8-11.8,29-18.6,41-14.2 C944.1,228.3,945.7,252.8,924.2,274.2z"
                    />
                    <path
                        fill="#dc2626"
                        d="M915.8,265.8c-14.1,14.1-30.8,14.6-36,4.1c-4.1-8.3,0.5-21.3,9.7-30.5s22.2-13.8,30.5-9.7 C930.4,235,929.9,251.7,915.8,265.8z"
                    />
                    <path
                        fill="#FFF"
                        d="M908.9,258.9c-8,8-17.9,9.2-21.6,3.5c-3.2-4.9-0.5-13.4,5.6-19.5c6.1-6.1,14.6-8.8,19.5-5.6 C918.1,241,916.9,250.9,908.9,258.9z"
                    />
                    <path
                        fill="#dc2626"
                        d="M903.2,253.2c-2.9,2.9-6.7,3.6-8.3,1.7c-1.5-1.8-0.6-5.4,2-8c2.6-2.6,6.2-3.6,8-2 C906.8,246.5,906.1,250.2,903.2,253.2z"
                    />
                </g>

                {/* Bow */}
                <g
                    ref={bowRef}
                    id="bow"
                    fill="none"
                    strokeLinecap="round"
                    vectorEffect="non-scaling-stroke"
                    pointerEvents="none"
                >
                    <polyline
                        ref={bowPolylineRef}
                        fill="none"
                        stroke="#a8a29e"
                        strokeLinecap="round"
                        points="88,200 88,250 88,300"
                    />
                    <path
                        fill="none"
                        stroke="#f59e0b"
                        strokeWidth="3"
                        strokeLinecap="round"
                        d="M88,300 c0-10.1,12-25.1,12-50s-12-39.9-12-50"
                    />
                </g>

                {/* Arrow being aimed */}
                <g ref={arrowAngleRef} className="arrow-angle">
                    <use ref={arrowUseRef} x="100" y="250" href="#arrow" />
                </g>

                {/* Clip path for arrows */}
                <clipPath id="mask">
                    <polygon
                        opacity=".5"
                        points="0,0 1500,0 1500,200 970,290 950,240 925,220 875,280 890,295 920,310 0,350"
                        pointerEvents="none"
                    />
                </clipPath>

                {/* Arrows container */}
                <g ref={arrowsRef} className="arrows" clipPath="url(#mask)" pointerEvents="none" />

                {/* Miss text */}
                <g
                    ref={missRef}
                    className="miss"
                    fill="#78716c"
                    opacity="0"
                    transform="translate(0, 100)"
                >
                    <path d="M358 194L363 118 386 120 400 153 416 121 440 119 446 203 419 212 416 163 401 180 380 160 381 204" />
                    <path d="M450 120L458 200 475 192 474 121" />
                    <path d="M537 118L487 118 485 160 515 162 509 177 482 171 482 193 529 199 538 148 501 146 508 133 537 137" />
                    <path d="M540 202L543 178 570 186 569 168 544 167 546 122 590 116 586 142 561 140 560 152 586 153 586 205" />
                    <path d="M595,215l5-23l31,0l-5,29L595,215z M627,176l13-70l-41-0l-0,70L627,176z" />
                </g>

                {/* Bullseye text */}
                <g ref={bullseyeRef} className="bullseye" fill="#dc2626" opacity="0">
                    <path d="M322,159l15-21l-27-13l-32,13l15,71l41-14l7-32L322,159z M292,142h20l3,8l-16,8 L292,142z M321,182l-18,9l-4-18l23-2V182z" />
                    <path d="M340 131L359 125 362 169 381 167 386 123 405 129 392 183 351 186z" />
                    <path d="M413 119L402 188 450 196 454 175 422 175 438 120z" />
                    <path d="M432 167L454 169 466 154 451 151 478 115 453 113z" />
                    <path d="M524 109L492 112 466 148 487 155 491 172 464 167 463 184 502 191 513 143 487 141 496 125 517 126z" />
                    <path d="M537 114L512 189 558 199 566 174 533 175 539 162 553 164 558 150 543 145 547 134 566 148 575 124z" />
                    <path d="M577 118L587 158 570 198 587 204 626 118 606 118 598 141 590 112z" />
                    <path d="M635 122L599 198 643 207 649 188 624 188 630 170 639 178 645 162 637 158 649 143 662 151 670 134z" />
                    <path d="M649,220l4-21l28,4l-6,25L649,220z M681,191l40-79l-35-8L659,184L681,191z" />
                </g>

                {/* Hit text */}
                <g
                    ref={hitRef}
                    className="hit"
                    fill="#f59e0b"
                    opacity="0"
                    transform="translate(180, -80) rotate(12)"
                >
                    <path d="M383 114L385 195 407 191 406 160 422 155 418 191 436 189 444 112 423 119 422 141 407 146 400 113" />
                    <path d="M449 185L453 113 477 112 464 186" />
                    <path d="M486 113L484 130 506 130 481 188 506 187 520 131 540 135 545 119" />
                    <path d="M526,195l5-20l22,5l-9,16L526,195z M558,164l32-44l-35-9l-19,51L558,164z" />
                </g>
            </svg>
        </div>
    );
}
