# Tree-sitter-blade Parameter Parsing Fix Specification

## Problem Description

The current tree-sitter-blade parser fails to correctly parse complex PHP array expressions within Blade directive parameters, specifically for the `@class` directive. The parser incorrectly splits multi-line array expressions at commas, treating each line as a separate parameter instead of recognizing the entire array structure as one cohesive parameter.

### Current Failing Code Example

```blade
<div @class([
    'text-rose-400 bg-rose-400/10' => $table->latestDump?->isError(),
    'text-green-400 bg-green-400/10' => $table->latestDump?->isSuccess(),
    'text-orange-400 bg-orange-400/10' => $table->latestDump?->isProcessing(),
    'flex-none',
    'rounded-full',
    'p-1'
])></div>
```

### Current Parser Output (Incorrect)

```
(attribute [0, 5] - [7, 30]
  (directive [0, 5] - [0, 11])
  (parameter [0, 12] - [1, 90])     // First line only
  (parameter [1, 90] - [1, 92])     // Split incorrectly
  (parameter [1, 92] - [2, 94])     // Split incorrectly
  (parameter [2, 94] - [2, 96])     // Split incorrectly
  // ... more incorrect splits
)
```

### Expected Parser Output (Correct)

```
(attribute [0, 5] - [7, 30]
  (directive [0, 5] - [0, 11])
  (parameter [0, 12] - [7, 29])     // Entire array as one parameter
)
```

## Root Cause Analysis

The issue is in the `parameter` rule definition in `grammar.js` at lines 947-948:

```javascript
parameter: ($) => choice(/[^()]+/, $._nested_parenthases),
_nested_parenthases: ($) => seq("(", repeat($.parameter), ")"),
```

### Current Limitations

1. **Square Bracket Blindness**: The regex `/[^()]+/` only excludes parentheses but doesn't handle square brackets `[...]`
2. **No Bracket Balancing**: No mechanism to balance square brackets like it does for parentheses
3. **Complex Expression Handling**: Doesn't account for PHP method chaining (`->`) and complex expressions within arrays
4. **Multi-line Structure**: Fails to recognize that array structures can span multiple lines

## Required Changes

### 1. Grammar Rule Modifications

**File**: `grammar.js`

**Current Code** (lines 947-948):
```javascript
parameter: ($) => choice(/[^()]+/, $._nested_parenthases),
_nested_parenthases: ($) => seq("(", repeat($.parameter), ")"),
```

**Proposed Changes**:

```javascript
parameter: ($) => choice(
  /[^()[\]]+/,                    // Simple text (exclude both () and [])
  $._nested_parenthases,          // Existing parentheses handling
  $._nested_brackets              // NEW: Square bracket handling
),

_nested_parenthases: ($) => seq("(", repeat($.parameter), ")"),

// NEW RULE: Handle square bracket balancing
_nested_brackets: ($) => seq("[", repeat($.parameter), "]"),
```

### 2. Enhanced Parameter Parsing

**Alternative Approach** (More Robust):

```javascript
parameter: ($) => choice(
  $._simple_parameter,
  $._nested_parenthases,
  $._nested_brackets
),

// Simple parameter that stops at unbalanced delimiters
_simple_parameter: (_) => /[^()[\],]+/,

_nested_parenthases: ($) => seq("(", repeat($.parameter), ")"),

_nested_brackets: ($) => seq("[", repeat($.parameter), "]"),
```

### 3. Test Cases to Add

**File**: `test/corpus/attributes.txt`

Add the following test cases after the existing `@class` test:

```
================================================================================
@class with complex array
================================================================================

<div @class([
    'text-rose-400 bg-rose-400/10' => $table->latestDump?->isError(),
    'text-green-400 bg-green-400/10' => $table->latestDump?->isSuccess(),
    'flex-none',
    'rounded-full'
])></div>

--------------------------------------------------------------------------------

(document
  (element
    (start_tag
      (tag_name)
      (attribute
        (directive)
        (parameter)))
    (end_tag
      (tag_name))))

================================================================================
@class with nested arrays
================================================================================

<div @class([
    'base-class',
    $condition ? ['conditional-class'] : [],
    'another-class'
])></div>

--------------------------------------------------------------------------------

(document
  (element
    (start_tag
      (tag_name)
      (attribute
        (directive)
        (parameter)))
    (end_tag
      (tag_name))))

================================================================================
@class with method chaining
================================================================================

<div @class([
    'error' => $model->hasErrors(),
    'success' => $model->isValid(),
])></div>

--------------------------------------------------------------------------------

(document
  (element
    (start_tag
      (tag_name)
      (attribute
        (directive)
        (parameter)))
    (end_tag
      (tag_name))))
```

### 4. Additional Edge Cases to Consider

1. **Nested Function Calls**:
   ```blade
   @class(['class' => someFunction(['nested', 'array'])])
   ```

2. **Mixed Brackets and Parentheses**:
   ```blade
   @class(['class' => $obj->method(['param'])])
   ```

3. **String Literals with Brackets**:
   ```blade
   @class(['class-[100px]' => $condition])
   ```

## Implementation Steps

### Step 1: Update Grammar Rules
1. Modify the `parameter` rule in `grammar.js`
2. Add `_nested_brackets` rule
3. Update the simple parameter regex to exclude square brackets

### Step 2: Add Comprehensive Tests
1. Add test cases for complex arrays in `test/corpus/attributes.txt`
2. Include edge cases with nested structures
3. Test method chaining scenarios

### Step 3: Regenerate Parser
1. Run `tree-sitter generate` to regenerate the parser
2. Test against the problematic code example
3. Verify all existing tests still pass

### Step 4: Validation
1. Test with the original failing code
2. Ensure the entire array is parsed as one parameter
3. Verify no regressions in existing functionality

## Expected Outcome

After implementing these changes:

1. **Correct Parsing**: The entire array expression should be parsed as a single parameter
2. **Bracket Balancing**: Square brackets should be properly balanced like parentheses
3. **Complex Expression Support**: PHP method chaining and complex expressions should work
4. **Multi-line Support**: Arrays spanning multiple lines should parse correctly
5. **Backward Compatibility**: All existing tests should continue to pass

## Files to Modify

1. **`grammar.js`** - Update parameter parsing rules
2. **`test/corpus/attributes.txt`** - Add comprehensive test cases
3. **Regenerated files** (via `tree-sitter generate`):
   - `src/parser.c`
   - `src/node-types.json`
   - `src/grammar.json`

## Testing Command

After implementation, test with:

```bash
tree-sitter parse test_problematic.blade.php
```

The output should show the entire array as one parameter instead of multiple split parameters.