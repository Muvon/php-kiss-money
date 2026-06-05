# Changelog

## [0.5.0] - 2026-06-05

### 📋 Release Summary

This release introduces the ability to create money objects directly from hexadecimal strings (f1dc6d48), providing greater flexibility in how monetary values are initialized.


### ✨ New Features & Enhancements

- **money**: add ability to create object from hex string `f1dc6d48`

All notable changes to this project will be documented in this file.

## [0.4.4] - 2026-06-05

### 📋 Release Summary

This release introduces comprehensive currency conversion support, including exchange rate detection and new utility methods for creating and manipulating money objects (41df7e6e, d6ddab0a, dc70bdec, d8d318e1). Formatting performance has been significantly optimized for converting values to amounts (a835b4f9, 1f7bc902, cdfb9548), while overall reliability was improved through various fixes to precision, negative value handling, and object instantiation (5e2f5874, b436495d, 60638123, 99177fc8).


### 🔧 Improvements & Optimizations

- **test**: add phpunit configuration file `89c39a17`

### 🐛 Bug Fixes & Stability

- **money**: improve precision and update dependencies `5e2f5874`
- Fix issue when we convert value to amount in wrong way and trim zero digits `b436495d`
- Fix trimmable display of value to amount `c5b7cd6e`
- Fix negative value issue and add test for it `60638123`
- Fix issue with detecting rate `17bf7f6b`
- Fix missprint `907f18e4`
- Fix method to be static `22ab0b63`
- Fix issue with fromValue object creation `99177fc8`

### 🔄 Other Changes

24 maintenance, dependency, and tooling updates not listed individually.
