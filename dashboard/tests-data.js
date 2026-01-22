/**
 * Generates the list of test cases based on dynamic environment IDs.
 * @param {number} helperEntryId - ID of a valid entry in the Helper scheme
 * @param {number} forbiddenEntryId - ID of an entry in the Forbidden scheme
 * @param {number} uploadId - ID of the uploaded test file (text/plain, ~12 bytes)
 */
function getTestCases(helperEntryId, forbiddenEntryId, uploadId) {
    // Base valid payload ("Golden Sample")
    const valid = {
        s_len: "abc", s_enum: "A", s_reg: "123", s_hex: "#FFFFFF", s_json: "{\"a\":1}", s_html: "<b>ok</b>",
        n_int: 15, n_float: 10.5, b_bool: true, ref: helperEntryId, arr_str: ["t1", "t2"], opt: "opt",
        u_valid: uploadId
    };

    return [
        // --- GLOBAL INTEGRITY ---
        { l: "Global: Nominal Case", exp: true, d: { ...valid } },
        { l: "Global: Ghost Field", exp: false, d: { ...valid, ghost: "boo" } },
        { l: "Global: Missing Required", exp: false, d: { ...valid, s_len: undefined } },
        { l: "Global: Required is Null", exp: false, d: { ...valid, s_len: null } },
        { l: "Global: Required is Empty String", exp: false, d: { ...valid, s_len: "" } },

        // --- STRING: LENGTH ---
        { l: "Str: Too short (1)", exp: false, d: { ...valid, s_len: "a" } },
        { l: "Str: Min length exact (2)", exp: true, d: { ...valid, s_len: "ab" } },
        { l: "Str: Max length exact (5)", exp: true, d: { ...valid, s_len: "abcde" } },
        { l: "Str: Too long (6)", exp: false, d: { ...valid, s_len: "abcdef" } },
        { l: "Str: Type Int instead of String", exp: false, d: { ...valid, s_len: 123 } },
        { l: "Str: Type Bool instead of String", exp: false, d: { ...valid, s_len: true } },

        // --- STRING: ENUM ---
        { l: "Enum: Valid Value B", exp: true, d: { ...valid, s_enum: "B" } },
        { l: "Enum: Invalid Value C", exp: false, d: { ...valid, s_enum: "C" } },
        { l: "Enum: Case Sensitive (a)", exp: false, d: { ...valid, s_enum: "a" } },
        { l: "Enum: Empty String", exp: false, d: { ...valid, s_enum: "" } },

        // --- STRING: REGEX ---
        { l: "Regex: Valid match", exp: true, d: { ...valid, s_reg: "999" } },
        { l: "Regex: Alpha fail", exp: false, d: { ...valid, s_reg: "abc" } },
        { l: "Regex: Too many digits", exp: false, d: { ...valid, s_reg: "1234" } },
        { l: "Regex: Too few digits", exp: false, d: { ...valid, s_reg: "12" } },
        { l: "Regex: Special chars", exp: false, d: { ...valid, s_reg: "1.2" } },

        // --- FORMAT: HEX COLOR ---
        { l: "Hex: Valid 6 chars", exp: true, d: { ...valid, s_hex: "#000000" } },
        { l: "Hex: Valid 3 chars", exp: true, d: { ...valid, s_hex: "#F0F" } },
        { l: "Hex: Missing hash", exp: false, d: { ...valid, s_hex: "FFFFFF" } },
        { l: "Hex: Bad char G", exp: false, d: { ...valid, s_hex: "#GGFFFF" } },
        { l: "Hex: Too long", exp: false, d: { ...valid, s_hex: "#FFFFFFF" } },
        { l: "Hex: Too short", exp: false, d: { ...valid, s_hex: "#FF" } },

        // --- FORMAT: JSON ---
        { l: "JSON: Valid Array", exp: true, d: { ...valid, s_json: "[1,2]" } },
        { l: "JSON: Valid Scalar", exp: true, d: { ...valid, s_json: "\"string\"" } },
        { l: "JSON: Valid Int String", exp: true, d: { ...valid, s_json: "123" } },
        { l: "JSON: Invalid Syntax", exp: false, d: { ...valid, s_json: "{key:val}" } },
        { l: "JSON: Trailing comma", exp: false, d: { ...valid, s_json: "[1,]" } },

        // --- FORMAT: HTML ---
        { l: "HTML: Valid complex", exp: true, d: { ...valid, s_html: "<div class='a'>Test</div>" } },
        { l: "HTML: Invalid unclosed tag", exp: false, d: { ...valid, s_html: "<div><span>Text</div>" } },
        { l: "HTML: Invalid root", exp: false, d: { ...valid, s_html: "<root" } },
        { l: "HTML: Empty", exp: false, d: { ...valid, s_html: "" } },

        // --- NUMBER: INT & RANGE ---
        { l: "Int: Min value (10)", exp: true, d: { ...valid, n_int: 10 } },
        { l: "Int: Max value (20)", exp: true, d: { ...valid, n_int: 20 } },
        { l: "Int: Under min (9)", exp: false, d: { ...valid, n_int: 9 } },
        { l: "Int: Over max (21)", exp: false, d: { ...valid, n_int: 21 } },
        { l: "Int: Float passed (10.5)", exp: false, d: { ...valid, n_int: 10.5 } },
        { l: "Int: String '15'", exp: false, d: { ...valid, n_int: "15" } },
        { l: "Int: String '15.5'", exp: false, d: { ...valid, n_int: "15.5" } },
        { l: "Int: Null", exp: false, d: { ...valid, n_int: null } },

        // --- NUMBER: FLOAT & STEP ---
        { l: "Float: Valid step (10.0)", exp: true, d: { ...valid, n_float: 10.0 } },
        { l: "Float: Valid step (10.5)", exp: true, d: { ...valid, n_float: 10.5 } },
        { l: "Float: Bad step (10.2)", exp: false, d: { ...valid, n_float: 10.2 } },
        { l: "Float: Very small bad step (10.001)", exp: false, d: { ...valid, n_float: 10.001 } },
        { l: "Float: String '10.5'", exp: false, d: { ...valid, n_float: "10.5" } },
        { l: "Float: Negative Valid (-10.5)", exp: true, d: { ...valid, n_float: -10.5 } },
        { l: "Float: Zero Valid", exp: true, d: { ...valid, n_float: 0.0 } },

        // --- BOOLEAN ---
        { l: "Bool: True", exp: true, d: { ...valid, b_bool: true } },
        { l: "Bool: False", exp: true, d: { ...valid, b_bool: false } },
        { l: "Bool: Int 1", exp: false, d: { ...valid, b_bool: 1 } },
        { l: "Bool: Int 0", exp: false, d: { ...valid, b_bool: 0 } },
        { l: "Bool: String 'true'", exp: false, d: { ...valid, b_bool: "true" } },
        { l: "Bool: Null", exp: false, d: { ...valid, b_bool: null } },

        // --- ENTRIES / REFERENCES ---
        { l: "Ref: Valid ID", exp: true, d: { ...valid, ref: helperEntryId } },
        { l: "Ref: Non-existent ID", exp: false, d: { ...valid, ref: 999999 } },
        { l: "Ref: Forbidden Scheme ID", exp: false, d: { ...valid, ref: forbiddenEntryId } },
        { l: "Ref: String ID", exp: false, d: { ...valid, ref: "" + helperEntryId } },
        { l: "Ref: Float ID", exp: false, d: { ...valid, ref: 1.5 } },
        { l: "Ref: Null", exp: false, d: { ...valid, ref: null } },

        // --- ARRAYS ---
        { l: "Arr: Valid (2 items)", exp: true, d: { ...valid, arr_str: ["A", "B"] } },
        { l: "Arr: Valid (3 items)", exp: true, d: { ...valid, arr_str: ["A", "B", "C"] } },
        { l: "Arr: Too short (1 item)", exp: false, d: { ...valid, arr_str: ["A"] } },
        { l: "Arr: Too long (4 items)", exp: false, d: { ...valid, arr_str: ["A", "B", "C", "D"] } },
        { l: "Arr: Empty", exp: false, d: { ...valid, arr_str: [] } },
        { l: "Arr: Not an array (String)", exp: false, d: { ...valid, arr_str: "A,B" } },
        { l: "Arr: Not an array (Obj)", exp: false, d: { ...valid, arr_str: { 0: "A" } } },
        { l: "Arr: Mixed Types (Int inside)", exp: false, d: { ...valid, arr_str: ["A", 123] } },

        // --- OPTIONAL FIELDS ---
        { l: "Opt: Omitted", exp: true, d: { ...valid, opt: undefined } },
        { l: "Opt: Explicit Value", exp: true, d: { ...valid, opt: "Value" } },
        { l: "Opt: Empty String (Allowed for string)", exp: true, d: { ...valid, opt: "" } },
        { l: "Opt: Null (Type must still match)", exp: false, d: { ...valid, opt: null } },
        { l: "Opt: Wrong Type", exp: false, d: { ...valid, opt: 123 } },

        // --- MASSIVE PAYLOAD ---
        {
            l: "Massive Payload (Stress test)",
            exp: true,
            d: {
                ...valid,
                s_json: JSON.stringify(Array(100).fill("data")),
                s_html: "<div>" + "p".repeat(1000) + "</div>"
            }
        },

        // --- UPLOADS VALIDATION ---
        { l: "Up: Valid ID & Type", exp: true, d: { ...valid, u_valid: uploadId } },
        { l: "Up: Non-existent ID", exp: false, d: { ...valid, u_valid: 999999 } },
        { l: "Up: Null on Required", exp: false, d: { ...valid, u_valid: null } },
        { l: "Up: Invalid Mime Type (Text vs Img)", exp: false, d: { ...valid, u_bad_mime: uploadId } },
        { l: "Up: Invalid Size (Too small)", exp: false, d: { ...valid, u_bad_size: uploadId } }
    ];
}