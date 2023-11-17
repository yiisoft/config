# Yii Config Change Log

## 1.3.2 under development

- Enh #152: Add plugin option "package-types" that define package types for process, by default "library" and
  "composer-plugin" (@vjik) 

## 1.3.1 November 17, 2023

- Bug #145: Use composer library and plugins only, instead of any packages before (@vjik)
- Bug #150: Empty configuration groups from packages were not added to merge plan (@vjik)

## 1.3.0 February 11, 2023

- Enh #131: Add ability to use `Config` without params (@vjik)

## 1.2.0 February 08, 2023

- Enh #119: Improve performance of collecting data for `ReverseMerge` and `RecursiveMerge` (@samdark)
- Enh #122: Raise minimal PHP version to 8.0 (@vjik, @xepozz)
- Enh #130: Add ability to change merge plan file path (@vjik)

## 1.1.1 January 05, 2022

- Enh #110: Improve the error message by displaying a name of the group where the error occurred when merging (@devanych)

## 1.1.0 December 31, 2021

- New #108: Add `Yiisoft\Config\ConfigInterface` to allow custom implementations of a config loader (@devanych)

## 1.0.0 December 17, 2021

- Initial release.
