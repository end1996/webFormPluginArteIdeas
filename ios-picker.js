(function () {
    'use strict';

    const styles = `
    <style>
        :root {
            --highlight-color: rgba(0, 122, 255, 0.1);
            --text-color: #333;
            --selected-text-color: #e76f51;
            --item-height: 40px;
            --visible-items: 3;
        }

        .carousel-container {
            position: relative;
            width: 100%;
            height: calc(var(--item-height) * var(--visible-items));
            overflow: hidden;
            background-color: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            touch-action: pan-y;
            margin: 0 auto;
        }

        .carousel-track {
            position: absolute;
            width: 100%;
            transition: transform 0.2s ease-out; /* Reduce la duración */
            color: #070707;
            will-change: transform;
        }

        .carousel-item {
            height: var(--item-height);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            color:  color: #070707;;
            // padding: 0 20px;
            cursor: pointer;
            transition: color 0.2s, transform 0.2s;
            user-select: none;
        }

        .selector-highlight {
            position: absolute;
            left: 10px;
            right: 10px;
            top: 50%;
            height: var(--item-height);
            background-color: var(--highlight-color);
            border-radius: 8px;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .carousel-item.selected {
            color: var(--selected-text-color);
            font-weight: 500;
        }

        .carousel-container::before,
        .carousel-container::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            height: calc(var(--item-height) * 2);
            z-index: 2;
            pointer-events: none;
        }

        
        .picker-title {
            text-align: center;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 600;
            color: var(--text-color);
        }

        .picker-wrapper {
            display: 20px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
    </style>
    `;

    class Picker {
        constructor(options) {
            this.options = Object.assign({
                items: [],
                initialValue: null,
                onChange: null,
            }, options);
            this.itemHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--item-height'));
            this.visibleItems = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--visible-items'));
            this.centerOffset = Math.floor(this.visibleItems / 2); // Precalcula el offset

            this.init();
        }

        init() {
            this.injectStyles();
            this.createPicker();
            this.initCarousel();
            this.setupKeyboardNavigation();
        }

        injectStyles() {
            if (!document.getElementById('ios-picker-styles')) {
                const styleElement = document.createElement('div');
                styleElement.id = 'ios-picker-styles';
                styleElement.innerHTML = styles;
                document.head.insertBefore(styleElement, document.head.firstChild);
            }
        }

        createPicker() {
            this.pickerWrapper = document.createElement('div');
            this.pickerWrapper.className = 'picker-wrapper';

            this.titleElement = document.createElement('h2');
            this.titleElement.className = 'picker-title';
            this.titleElement.textContent = this.options.title;

            this.carouselContainer = document.createElement('div');
            this.carouselContainer.className = 'carousel-container';

            this.carouselTrack = document.createElement('div');
            this.carouselTrack.className = 'carousel-track';

            const items = this.generateItems();
            this.carouselTrack.innerHTML = items;

            const highlight = document.createElement('div');
            highlight.className = 'selector-highlight';

            this.carouselContainer.appendChild(highlight);
            this.carouselContainer.appendChild(this.carouselTrack);
            this.pickerWrapper.appendChild(this.titleElement);
            this.pickerWrapper.appendChild(this.carouselContainer);

            if (this.options.container) {
                this.options.container.appendChild(this.pickerWrapper);
            } else {
                document.body.appendChild(this.pickerWrapper);
            }
        }

        generateItems() {
            const items = this.options.items;
            const copies = [...items.slice(-3), ...items, ...items.slice(0, 3)];

            return copies.map(item => `
                <div class="carousel-item" data-value="${item.value}">
                    ${item.label}
                </div>
            `).join('');
        }

        initCarousel() {
            this.carousel = new EmblaCarouselMinimal({
                element: this.carouselContainer,
                trackElement: this.carouselTrack,
                onChange: (value, index) => {
                    if (this.options.onChange) {
                        this.options.onChange(value, index);
                    }
                }
            });

            if (this.options.initialValue) {
                const index = this.options.items.findIndex(item => item.value === this.options.initialValue);
                if (index > -1) this.carousel.goToIndex(index);
            }
        }

        setupKeyboardNavigation() {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowUp') this.carousel.prev();
                if (e.key === 'ArrowDown') this.carousel.next();
            });
        }

        getValue() {
            return this.carousel.getValue();
        }

        destroy() {
            this.pickerWrapper.remove();
            document.getElementById('ios-picker-styles')?.remove();
        }
    }

    class EmblaCarouselMinimal {
        constructor(options) {
            this.element = options.element;
            this.trackElement = options.trackElement;
            this.onChange = options.onChange || (() => { });
            this.items = Array.from(this.trackElement.querySelectorAll('.carousel-item'));
            this.itemHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--item-height'));
            this.visibleItems = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--visible-items'));
            this.mainItemsStart = 3;
            this.mainItemsCount = this.items.length - 6; // Calcula basado en los items generados
            this.infiniteLoop = true;
            this.currentIndex = 0;
            this.startY = 0;
            this.startTranslate = 0;
            this.currentTranslate = 0;
            this.isDragging = false;
            this.isAnimating = false;
            this.transitionSkip = false;
            this.init();
        }

        init() {
            this.items.forEach((item, index) => {
                item.dataset.index = index;
                item.addEventListener('click', () => this.onItemClick(index));
            });
            this.setupEvents();
            this.goToIndex(this.currentIndex, false);
            this.highlightSelected();
        }

        setupEvents() {
            this.element.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
            this.element.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
            this.element.addEventListener('touchend', this.handleTouchEnd.bind(this));
            this.element.addEventListener('mousedown', this.handleMouseDown.bind(this));
            document.addEventListener('mousemove', this.handleMouseMove.bind(this));
            document.addEventListener('mouseup', this.handleMouseUp.bind(this));
            this.element.addEventListener('wheel', this.handleWheel.bind(this), { passive: false });
        }

        onItemClick(index) {
            const centerOffset = Math.floor(this.visibleItems / 2);
            const centerPosition = -this.currentTranslate + (centerOffset * this.itemHeight);
            const currentCenterIndex = Math.round(centerPosition / this.itemHeight);
            const steps = index - currentCenterIndex;
            if (steps === 0) return;
            let relativeIndex = index - this.mainItemsStart;
            if (relativeIndex < 0) relativeIndex += this.mainItemsCount;
            else if (relativeIndex >= this.mainItemsCount) relativeIndex -= this.mainItemsCount;
            this.goToIndex(relativeIndex, true);
        }

        handleTouchStart(e) {
            if (this.isDragging || this.isAnimating) return;
            e.preventDefault();
            this.startY = e.touches[0].clientY;
            this.startTranslate = this.currentTranslate;
            this.isDragging = true;
            this.trackElement.style.transition = 'none';
        }

        handleTouchMove(e) {
            if (!this.isDragging) return;
            e.preventDefault();
            const currentY = e.touches[0].clientY;
            const diff = currentY - this.startY;
            this.currentTranslate = this.startTranslate + diff;
            this.updatePosition(true);
        }

        handleTouchEnd() {
            if (!this.isDragging) return;
            this.isDragging = false;

            const moved = this.currentTranslate - this.startTranslate;
            const movementInItems = Math.round(-moved / this.itemHeight);
            let newIndex = this.currentIndex + movementInItems;

            if (this.infiniteLoop) {
                newIndex = (newIndex + this.mainItemsCount) % this.mainItemsCount;
            } else {
                newIndex = Math.max(0, Math.min(this.mainItemsCount - 1, newIndex));
            }

            this.goToIndex(newIndex, true);
        }
        handleMouseDown(e) {
            if (this.isDragging || this.isAnimating) return;
            e.preventDefault();
            this.startY = e.clientY;
            this.startTranslate = this.currentTranslate;
            this.isDragging = true;
            this.element.style.cursor = 'grabbing';
            this.trackElement.style.transition = 'none';
        }

        handleMouseMove(e) {
            if (!this.isDragging) return;
            e.preventDefault();
            const currentY = e.clientY;
            const diff = currentY - this.startY;
            this.currentTranslate = this.startTranslate + diff;
            this.updatePosition(true);
        }

        handleMouseUp() {
            if (!this.isDragging) return;
            this.isDragging = false;
            this.element.style.cursor = '';

            const moved = this.currentTranslate - this.startTranslate;
            const movementInItems = Math.round(-moved / this.itemHeight);
            let newIndex = this.currentIndex + movementInItems;

            if (this.infiniteLoop) {
                newIndex = (newIndex + this.mainItemsCount) % this.mainItemsCount;
            } else {
                newIndex = Math.max(0, Math.min(this.mainItemsCount - 1, newIndex));
            }

            this.goToIndex(newIndex, true);
        }

        handleWheel(e) {
            e.preventDefault();

            if (this.isAnimating) return;

            const now = Date.now();
            if (now - (this.lastWheelTime || 0) < 200) return; // Aumenta el intervalo
            this.lastWheelTime = now;

            const direction = e.deltaY > 0 ? 1 : -1;
            let newIndex = this.currentIndex + direction;

            if (this.infiniteLoop) {
                newIndex = (newIndex + this.mainItemsCount) % this.mainItemsCount;
            } else {
                newIndex = Math.max(0, Math.min(this.mainItemsCount - 1, newIndex));
            }

            this.goToIndex(newIndex, true);
        }

        goToIndex(index, animate = true) {

            if (this.isAnimating) return; // Evita bloqueos
            this.isAnimating = true;

            if (this.infiniteLoop) {
                this.currentIndex = index;

                const centerOffset = Math.floor(this.visibleItems / 2);
                const actualIndex = this.mainItemsStart + index;
                this.currentTranslate = -((actualIndex - centerOffset) * this.itemHeight);

                if (animate) {
                    this.trackElement.style.transition = ' none';
                }

                this.updatePosition();
                this.highlightSelected();

                const selectedItem = this.items[this.mainItemsStart + index];
                this.onChange(selectedItem.dataset.value, index);

                if (animate) {
                    setTimeout(() => {
                        this.isAnimating = false;
                    }, 200); // Reduce el tiempo de espera
                } else {
                    this.isAnimating = false;
                }
            }
        }

        updatePosition(isDragging = false) {
            requestAnimationFrame(() => {
                this.trackElement.style.transform = `translateY(${this.currentTranslate}px)`;
                if (!isDragging) this.highlightSelected();
            });
        }

        highlightSelected() {
            this.items.forEach(item => item.classList.remove('selected'));
            const selectedItemIndex = this.mainItemsStart + this.currentIndex;
            if (selectedItemIndex >= 0 && selectedItemIndex < this.items.length) {
                this.items[selectedItemIndex].classList.add('selected');
            }
        }

        next() {
            let newIndex = this.currentIndex + 1;
            if (newIndex >= this.mainItemsCount) newIndex = 0;
            this.goToIndex(newIndex, true);
        }

        prev() {
            let newIndex = this.currentIndex - 1;
            if (newIndex < 0) newIndex = this.mainItemsCount - 1;
            this.goToIndex(newIndex, true);
        }

        getValue() {
            const selectedItem = this.items[this.mainItemsStart + this.currentIndex];
            return selectedItem ? selectedItem.dataset.value : null;
        }
    }

    window.iOSPicker = Picker;
})();