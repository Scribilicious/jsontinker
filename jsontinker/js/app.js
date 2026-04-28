// Auto-expand textareas based on content and initialize collapsible sections
document.addEventListener('DOMContentLoaded', function() {
    addEventListeners();
});

function addEventListeners() {
    const textareas = document.querySelectorAll('textarea');

    textareas.forEach(textarea => {
        // Set initial height
        textarea.style.height = 'auto';
        textarea.style.height = getTextHeight(textarea);

        // Update height on input
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = getTextHeight(this);
        });
    });

    // Initialize collapsible sections
    initCollapsibleSections();
}

function getTextHeight(element) {
    let height = element.scrollHeight;
    return height + 'px';
}

// Toggle sidebar visibility
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (toggleBtn && sidebar) {
        const overlay = document.querySelector('.sidebar-overlay');

        function toggleSidebar(forceState) {
            const isVisible = typeof forceState === 'boolean' ? forceState : !sidebar.classList.contains('visible');
            sidebar.classList.toggle('visible', isVisible);
            toggleBtn.classList.toggle('active', isVisible);
            if (overlay) overlay.classList.toggle('active', isVisible);
            const textSpan = toggleBtn.querySelector('.toggle-text');
            if (textSpan) {
                textSpan.textContent = isVisible ? 'Close' : 'Menu';
            }
        }

        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });

        // Close sidebar when clicking overlay or outside
        if (overlay) {
            overlay.addEventListener('click', function() {
                toggleSidebar(false);
            });
        }
    }
});

/**
 * Initialize collapsible sections
 */
function initCollapsibleSections() {
    document.querySelectorAll('.section-header').forEach(header => {
        // Remove existing event listeners to avoid duplicates
        header.removeEventListener('click', toggleCollapse);
        header.addEventListener('click', toggleCollapse);
    });
}

/**
 * Toggle collapsed state of a section
 */
function toggleCollapse(event) {
    // Don't toggle if the click was on a button or its child
    if (event.target.tagName === 'BUTTON' || event.target.closest('button')) {
        return;
    }

    const header = event.currentTarget;
    const section = header.closest('.nested-section');
    if (section) {
        section.classList.toggle('collapsed');
    }
}

/**
 * Close all collapsible groups
 */
function closeAllGroups() {
    document.querySelectorAll('.nested-section').forEach(section => {
        section.classList.add('collapsed');
    });
    const arrow = document.querySelector('.toggle-all-arrow');
    if (arrow) arrow.classList.add('collapsed');
}

/**
 * Toggle all collapsible groups
 */
function toggleAllGroups() {
    const sections = document.querySelectorAll('.nested-section');
    if (sections.length === 0) return;

    const firstSection = sections[0];
    const isCollapsed = firstSection.classList.contains('collapsed');
    
    sections.forEach(section => {
        section.classList.toggle('collapsed', !isCollapsed);
    });

    const arrow = document.querySelector('.toggle-all-arrow');
    if (arrow) {
        arrow.classList.toggle('collapsed', !isCollapsed);
    }
}

/**
 * Add a new item to an array
 */
function addArrayItem(button) {
    const container = button.previousElementSibling;
    if (!container || !container.classList.contains('array-container')) {
        console.error('Could not find array container');
        return;
    }

    const arrayName = container.dataset.arrayName;
    const currentLength = parseInt(container.dataset.arrayLength);
    const newIndex = currentLength;

    // Get the first existing item to determine structure
    const firstItem = container.querySelector('.array-item');

    let newItem;

    if (firstItem) {
        // Clone the first item's structure
        newItem = firstItem.cloneNode(true);
        newItem.dataset.index = newIndex;

        // Clear values based on input type
        clearItemValues(newItem);

        // Update labels, names, and IDs for the new index
        updateItemNames(newItem, arrayName, newIndex);
    } else {
        // No existing items, create a default textarea item
        newItem = document.createElement('div');
        newItem.className = 'form-group array-item';
        newItem.dataset.index = newIndex;

        // Create label
        const label = document.createElement('label');
        const baseName = arrayName.replace(/\.\d+$/, '');
        label.htmlFor = arrayName + '.' + newIndex;
        label.textContent = baseName + '[' + newIndex + ']';

        // Create field with actions div
        const fieldWithActions = document.createElement('div');
        fieldWithActions.className = 'field-with-actions';

        // Create textarea (default)
        const textarea = document.createElement('textarea');
        textarea.name = 'data[' + arrayName + '.' + newIndex + ']';
        textarea.id = arrayName + '.' + newIndex;
        textarea.rows = 1;

        // Create remove button
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn-remove-item';
        removeButton.textContent = 'Remove';
        removeButton.onclick = function() { removeArrayItem(this); };

        // Assemble
        fieldWithActions.appendChild(textarea);
        fieldWithActions.appendChild(removeButton);
        newItem.appendChild(label);
        newItem.appendChild(fieldWithActions);
    }

    // Insert before the "Add Item" button
    container.appendChild(newItem);

    // Update array length
    container.dataset.arrayLength = currentLength + 1;

    // Initialize auto-expand for any new textareas
    addEventListeners();
}

/**
 * Clear values in an array item based on input types
 */
function clearItemValues(item) {
    // Expand if this item is collapsed
    if (item.classList && item.classList.contains("collapsed")) {
        item.classList.remove("collapsed");
    }
    // Clear textareas
    const textareas = item.querySelectorAll("textarea");
    textareas.forEach(textarea => {
        textarea.value = "";
    });

    // Clear number inputs
    const numberInputs = item.querySelectorAll("input[type=\"number\"]");
    numberInputs.forEach(input => {
        input.value = "";
    });

    // Clear checkboxes (uncheck) and ensure hidden input is false
    const checkboxes = item.querySelectorAll("input[type=\"checkbox\"]");
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        // Find associated hidden input (previous sibling of type hidden with same name?)
        // For now, assume hidden input is sibling before checkbox
        const hidden = checkbox.previousElementSibling;
        if (hidden && hidden.type === "hidden") {
            hidden.value = "false";
        }
    });

    // Expand any collapsed nested sections and recursively clear values
    const nestedSections = item.querySelectorAll(".nested-section");
    nestedSections.forEach(section => {
        section.classList.remove("collapsed");
        clearItemValues(section);
    });
}

/**
 * Update names, IDs, and labels in an array item for a new index
 */
function updateItemNames(item, arrayName, newIndex) {
    // Update "Item X" or "Group X" text in section header if present
    const sectionHeader = item.querySelector(".section-header");
    if (sectionHeader) {
        // Check if header has "Group" as first strong element text
        const strongGroup = sectionHeader.querySelector("strong");
        const hasGroupLabel = strongGroup && strongGroup.textContent.trim() === "Group";
        // Find the text node between <strong>Group</strong> and <button>
        const textNodes = Array.from(sectionHeader.childNodes).filter(node => node.nodeType === Node.TEXT_NODE);
        if (textNodes.length > 0) {
            const txt = textNodes[0].textContent.trim();
            if (txt.startsWith("Item")) {
                textNodes[0].textContent = "Item " + (newIndex + 1) + " ";
            } else if (hasGroupLabel) {
                textNodes[0].textContent = " " + newIndex + " ";
            }
        }
    }

    // Escape regex special characters in arrayName
    const escapedArrayName = arrayName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    // Pattern to match arrayName.oldIndex
    const arrayPattern = new RegExp(escapedArrayName + '\.(\\d+)');

    // Update all inputs, textareas, and labels
    const inputs = item.querySelectorAll("input, textarea");
    inputs.forEach(input => {
        const oldName = input.name;
        if (oldName && oldName.startsWith("data[")) {
            // Extract the field path after data[ and before ]
            const match = oldName.match(/^data\[(.*)\]$/);
            if (match) {
                const fieldPath = match[1];
                // Replace arrayName.oldIndex with arrayName.newIndex
                const newFieldPath = fieldPath.replace(arrayPattern, arrayName + "." + newIndex);
                input.name = "data[" + newFieldPath + "]";
                input.id = newFieldPath;

                // Update corresponding label if it exists
                const label = item.querySelector('label[for="' + fieldPath + '"]');
                if (label) {
                    label.htmlFor = newFieldPath;
                }
            }
        }
    });

    // Also update any labels that weren't matched by the above (for nested sections)
    const labels = item.querySelectorAll("label");
    labels.forEach(label => {
        const oldFor = label.htmlFor;
        if (oldFor) {
            const newFor = oldFor.replace(arrayPattern, arrayName + "." + newIndex);
            if (newFor !== oldFor) {
                label.htmlFor = newFor;
            }
        }
    });

    // Update data-array-name attributes on nested array containers
    const arrayContainers = item.querySelectorAll(".array-container");
    arrayContainers.forEach(container => {
        const oldName = container.dataset.arrayName;
        if (oldName) {
            const newName = oldName.replace(arrayPattern, arrayName + "." + newIndex);
            if (newName !== oldName) {
                container.dataset.arrayName = newName;
            }
        }
    });
}

/**
 * Remove an array item and reindex remaining items
 */
function removeArrayItem(button) {
    const item = button.closest('.array-item');
    if (!item) {
        console.error('Could not find array item');
        return;
    }

    const container = item.closest('.array-container');
    if (!container) {
        console.error('Could not find array container');
        return;
    }

    // Remove the item
    item.remove();

    // Reindex remaining items
    reindexArray(container);
}

/**
 * Reindex array items after addition/removal
 */
function reindexArray(container) {
    const items = container.querySelectorAll('.array-item');
    const arrayName = container.dataset.arrayName;

    items.forEach((item, newIndex) => {
        item.dataset.index = newIndex;
        updateItemNames(item, arrayName, newIndex);
    });

    // Update container length
    container.dataset.arrayLength = items.length;
}
